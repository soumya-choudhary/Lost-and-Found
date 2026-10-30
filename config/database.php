<?php
// config/database.php - PostgreSQL PDO Connection Singleton

// Simple .env parser
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $v = trim($v, " \t\n\r\0\x0B\"'");
        putenv(trim($k) . "=$v");
        $_ENV[trim($k)] = $v;
    }
}

loadEnv(dirname(__DIR__) . '/.env');
loadEnv(__DIR__ . '/.env');

// PDO Singleton Database Handler
class Database {
    private static ?PDO $db = null;

    public static function getConnection(): PDO {
        if (self::$db === null) {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '5432';
            $name = getenv('DB_NAME') ?: 'lost_and_found_db';
            $user = getenv('DB_USER') ?: 'postgres';
            $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('DB_PASSWORD') ?: '');

            try {
                self::$db = new PDO("pgsql:host={$host};port={$port};dbname={$name};", $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                die("DB Connection Error: " . $e->getMessage());
            }
        }
        return self::$db;
    }
}

$pdo = Database::getConnection();
