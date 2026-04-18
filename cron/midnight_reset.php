<?php

/**
 * MIDNIGHT RESET CRON
 * Crontab: 0 0 * * * /usr/bin/php /var/www/html/mlm/cron/midnight_reset.php
 *
 * The ONLY job of this script:
 *   Reset pairs_paid_today = 0 for all active members.
 *   This clears the daily pairing cap so members can earn again tomorrow.
 *
 * All commission calculations are real-time and happen during registration.
 * This script has zero business logic — it is purely a counter reset.
 */

// Bootstrap
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'kensue_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_URL',  'http://localhost/kensue2');
define('APP_NAME', 'Kensue');
define('APP_ENV',  'production');

require_once __DIR__ . '/../config/db.php';

$ts = date('Y-m-d H:i:s');

try {
    $pdo = db();

    // Reset daily pair counters for all members
    $affected = $pdo->exec("UPDATE users SET pairs_paid_today = 0 WHERE role = 'member'");

    // Log the reset timestamp
    $pdo->prepare("UPDATE settings SET value = ? WHERE key_name = 'last_reset'")
        ->execute([$ts]);

    echo "[{$ts}] Midnight reset complete. Members reset: {$affected}\n";
} catch (\Exception $e) {
    echo "[{$ts}] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
