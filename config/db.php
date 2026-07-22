<?php
// ============================================================
// DATABASE CONNECTION — update credentials if needed
// ============================================================
define('DB_HOST',    'localhost');
define('DB_USER',    'root');      // XAMPP default
define('DB_PASS',    'P@ssw0rd');          // XAMPP default (empty)
define('DB_NAME',    'garagecl_db'); // Your database name
define('DB_CHARSET', 'utf8mb4');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['success'=>false,'error'=>'Database connection failed. Make sure MySQL is running in XAMPP. '.$e->getMessage()]));
        }
    }
    return $pdo;
}
