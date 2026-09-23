<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'sql208.byethost11.com');
define('DB_NAME', 'b11_42933766_bilguardsdb');
define('DB_USER', 'b11_42933766');
define('DB_PASS', 'bilguard2000');

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
