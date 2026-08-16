<?php
declare(strict_types=1);

/**
 * Genera config/database.php desde variables de entorno (CI/CD).
 * Uso: DB_HOST=localhost DB_NAME=... DB_USER=... DB_PASS=... php scripts/generate-database-config.php > config/database.php
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

$host = getenv('DB_HOST') ?: 'localhost';
$name = getenv('DB_NAME') ?: '';
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASS') ?: '';

foreach (['DB_NAME' => $name, 'DB_USER' => $user, 'DB_PASS' => $pass] as $label => $value) {
    if ($value === '') {
        fwrite(STDERR, "Falta $label\n");
        exit(1);
    }
}

$template = <<<'PHP'
<?php
define('DB_HOST', '%s');
define('DB_NAME', '%s');
define('DB_USER', '%s');
define('DB_PASS', '%s');

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

PHP;

echo sprintf(
    $template,
    addslashes($host),
    addslashes($name),
    addslashes($user),
    addslashes($pass)
);
