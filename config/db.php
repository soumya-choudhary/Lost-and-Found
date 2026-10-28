<?php
// config/db.php - Secure database connection with .env loader

// Helper function to load environment variables from .env file
function loadEnvFile($filePath) {
    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comment lines
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);

            // Strip surrounding quotes
            if (preg_match('/^(["\'])(.*)\1$/', $val, $matches)) {
                $val = $matches[2];
            }

            putenv("{$key}={$val}");
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}

// Load .env from root directory
loadEnvFile(dirname(__DIR__) . '/.env');

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_port = getenv('DB_PORT') ?: '5432';
$db_name = getenv('DB_NAME') ?: 'lost_and_found_db';
$db_user = getenv('DB_USER') ?: 'postgres';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

$dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please ensure PostgreSQL is running and check your .env settings.");
}
