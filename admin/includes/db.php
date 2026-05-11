<?php
/**
 * PDO database connection — Ambozy CRM
 * Configure DB_* constants in config.php (not committed to git)
 */
defined('AMBOZY_CRM') or die('Direct access not permitted.');

$cfg_file = __DIR__ . '/../../config.php';
if (file_exists($cfg_file)) {
    require_once $cfg_file;
} else {
    // Fallback defaults — override in config.php
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'ambozy_crm');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_PORT', '3306');
}

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
