<?php
/**
 * Plantilla de configuración MySQL para SANVALX.
 * Copia como config/database.php en el servidor y rellena los valores reales.
 * NO subas database.php a repositorios públicos.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'TU_BASE_DE_DATOS');
define('DB_USER', 'TU_USUARIO_MYSQL');
define('DB_PASS', 'TU_CONTRASEÑA_MYSQL');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
    }
    return $pdo;
}
