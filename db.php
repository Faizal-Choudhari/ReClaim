<?php
function env_value(string $key, string $default = ''): string
{
    // First, read environment variables provided by Railway or the OS.
    $value = getenv($key);

    if ($value !== false && $value !== '') {
        return $value;
    }

    // Otherwise, read settings from the local .env file.
    static $env = null;

    if ($env === null) {
        $env = [];
        $file = __DIR__ . '/.env';

        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);

                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                    continue;
                }

                [$k, $v] = explode('=', $line, 2);
                $env[trim($k)] = trim($v);
            }
        }
    }

    return $env[$key] ?? $default;
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = env_value('DB_HOST', '127.0.0.1');
        $port = env_value('DB_PORT', '3306');
        $name = env_value('DB_NAME', 'college_lost_found');
        $user = env_value('DB_USER', 'root');
        $pass = env_value('DB_PASS', '');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $name
        );

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());

            http_response_code(500);
            exit(
                '<h2>Database connection failed</h2>' .
                '<p>Please check the database configuration and server logs.</p>'
            );
        }
    }

    return $pdo;
}
