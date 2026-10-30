<?php
// config/database.php - PostgreSQL Database Connection Handler (PDO Singleton)
//
// WHAT THIS FILE DOES:
// 1. Reads configuration variables from .env file (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS).
// 2. Implements a Singleton Database::getConnection() method ensuring only one PDO connection
//    is opened during the PHP request lifecycle.
// 3. Configures PDO error mode to ERRMODE_EXCEPTION so SQL errors throw catchable exceptions.
// 4. Sets default fetch mode to FETCH_ASSOC (queries return associative arrays).
//
// HOW TO DEBUG & MODIFY:
// - TO CHANGE DATABASE CREDENTIALS: Edit the .env file in the project root:
//   DB_HOST=127.0.0.1
//   DB_PORT=5432
//   DB_NAME=lost_and_found_db
//   DB_USER=postgres
//   DB_PASS=Password@28
// - TO DEBUG CONNECTION ERRORS: Check that PostgreSQL service is started in Windows Services (services.msc).
// - IF PDO PGSQL EXTENSION IS MISSING: In C:\xampp\php\php.ini, ensure `extension=pdo_pgsql` and `extension=pgsql` are uncommented.

// Function to parse key=value lines from .env file into PHP environment
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        // Skip comments starting with '#' and empty lines
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        // Strip surrounding quotes
        $v = trim($v, " \t\n\r\0\x0B\"'");
        putenv(trim($k) . "=$v");
        $_ENV[trim($k)] = $v;
    }
}

// Check both project root and config/ folder for .env
loadEnv(dirname(__DIR__) . '/.env');
loadEnv(__DIR__ . '/.env');

// PDO Singleton Database Handler
class Database {
    private static ?PDO $db = null;

    // Return the active PDO connection instance
    public static function getConnection(): PDO {
        if (self::$db === null) {
            // Read environment variables or use safe defaults
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '5432';
            $name = getenv('DB_NAME') ?: 'lost_and_found_db';
            $user = getenv('DB_USER') ?: 'postgres';
            $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('DB_PASSWORD') ?: '');

            try {
                // Connect via PostgreSQL PDO DSN
                self::$db = new PDO("pgsql:host={$host};port={$port};dbname={$name};", $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on SQL errors
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Return associative arrays
                    PDO::ATTR_EMULATE_PREPARES   => false,                   // Use native PostgreSQL prepared statements
                ]);
            } catch (PDOException $e) {
                // If connection fails, output clear diagnostic message
                die("DB Connection Error: " . $e->getMessage() . "<br>Please check PostgreSQL service status and .env credentials.");
            }
        }
        return self::$db;
    }
}

// Global variable for backwards compatibility
$pdo = Database::getConnection();
