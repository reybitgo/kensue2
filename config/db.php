<?php
// ── Database Configuration ──────────────────────────────────────────────────
// Copy this file and fill in your real credentials.
// Never commit real credentials to version control.

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'kensue_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// ── Application Configuration ───────────────────────────────────────────────
define('APP_URL',  'http://localhost/kensue2');  // No trailing slash
define('APP_NAME', 'Kensue');
define('APP_ENV',  'development');            // 'development' | 'production'

// ── Error display ────────────────────────────────────────────────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

/**
 * Returns a singleton PDO connection.
 * Usage: db()->prepare(...)
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_FOUND_ROWS   => true,
            ]);
        } catch (PDOException $e) {
            if (APP_ENV === 'development') {
                die('<pre>Database connection failed: ' . $e->getMessage() . '</pre>');
            }
            die('Service temporarily unavailable. Please try again later.');
        }
    }

    return $pdo;
}
