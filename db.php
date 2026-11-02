<?php
/** SQLite database connection and first-run setup for ReClaim. */
function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value !== false && $value !== '') return $value;

    static $env = null;
    if ($env === null) {
        $env = [];
        $file = __DIR__ . '/.env';
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $env[trim($k)] = trim($v);
            }
        }
    }
    return $env[$key] ?? $default;
}

function initialize_database(PDO $pdo): void
{
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        usn TEXT NOT NULL UNIQUE,
        phone TEXT NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'student' CHECK (role IN ('student','admin')),
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT NOT NULL,
        category TEXT NOT NULL,
        location TEXT NOT NULL,
        item_type TEXT NOT NULL CHECK (item_type IN ('lost','found')),
        item_date TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'open' CHECK (status IN ('open','claimed','closed')),
        photo TEXT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS claims (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        item_id INTEGER NOT NULL,
        claimant_id INTEGER NOT NULL,
        message TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','approved','rejected')),
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
        FOREIGN KEY (claimant_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_items_status ON items(status)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_items_category ON items(category)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_items_location ON items(location)');

    // Add the sample records only on a completely new database.
    if ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        $users = $pdo->prepare('INSERT INTO users (name,email,usn,phone,password,role) VALUES (?,?,?,?,?,?)');
        $users->execute(['Portal Admin', 'admin@college.edu', 'ADMIN0001', '9000000001', '$2b$10$fMa35d0eZLWG7G54DFYvquK.PWcsCDk2QWWps6rpl2bwXCLRv1xI2', 'admin']);
        $users->execute(['Asha Student', 'asha@college.edu', '1BM24CS001', '9000000002', '$2b$10$SiOvlab7KkKcpzH76GQV.eXHetDJrACazV4KZ3zQ.lcM7WT2D7nFW', 'student']);
        $users->execute(['Ravi Student', 'ravi@college.edu', '1BM24CS002', '9000000003', '$2b$10$SiOvlab7KkKcpzH76GQV.eXHetDJrACazV4KZ3zQ.lcM7WT2D7nFW', 'student']);

        $items = $pdo->prepare('INSERT INTO items (user_id,title,description,category,location,item_type,item_date,status,photo) VALUES (?,?,?,?,?,?,?,?,?)');
        $today = new DateTimeImmutable('today');
        $items->execute([2, 'Black leather wallet', 'Black wallet with a college ID card and a few receipts inside.', 'Wallets', 'Library', 'lost', $today->modify('-3 days')->format('Y-m-d'), 'open', 'wallet.jpg']);
        $items->execute([3, 'Blue water bottle', 'Blue steel bottle with a sticker of a mountain on the side.', 'Accessories', 'Canteen', 'found', $today->modify('-2 days')->format('Y-m-d'), 'open', 'bottle.jpg']);
        $items->execute([3, 'Scientific calculator', 'Casio FX-991 calculator with initials R.K. on the back.', 'Electronics', 'Computer Lab', 'found', $today->modify('-1 day')->format('Y-m-d'), 'open', 'calculator.jpg']);
        $items->execute([2, 'Set of keys', 'Three keys on a red keychain.', 'Keys', 'Sports Ground', 'lost', $today->modify('-5 days')->format('Y-m-d'), 'open', 'keys.jpg']);
        $pdo->prepare('INSERT INTO claims (item_id,claimant_id,message,status) VALUES (?,?,?,?)')->execute([3, 2, 'This is my calculator, it has my initials A.S. scratched inside the battery cover.', 'pending']);
    }
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $defaultPath = is_dir('/var/data') && is_writable('/var/data')
        ? '/var/data/reclaim.sqlite'
        : __DIR__ . '/data/reclaim.sqlite';
    $dbPath = env_value('DB_PATH', $defaultPath);
    $directory = dirname($dbPath);
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create the SQLite database directory.');
    }

    try {
        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec('PRAGMA busy_timeout = 5000');
        initialize_database($pdo);
    } catch (Throwable $e) {
        error_log('SQLite database error: ' . $e->getMessage());
        http_response_code(500);
        exit('<h2>Database setup failed</h2><p>Check the Render logs and confirm the persistent disk is mounted at /var/data.</p>');
    }
    return $pdo;
}
