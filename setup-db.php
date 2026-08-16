<?php
declare(strict_types=1);

$setupKey = getenv('SANVALX_SETUP_KEY') ?: 'svx-setup-7f3k9m2p1q8w4n6r';
$provided = (string) ($_GET['key'] ?? $_POST['key'] ?? '');

if ($provided === '' || !hash_equals($setupKey, $provided)) {
    http_response_code(403);
    exit('Forbidden');
}

$configDir = __DIR__ . '/config';
$configFile = $configDir . '/database.php';

$host = (string) ($_POST['db_host'] ?? $_GET['db_host'] ?? 'localhost');
$name = (string) ($_POST['db_name'] ?? $_GET['db_name'] ?? '');
$user = (string) ($_POST['db_user'] ?? $_GET['db_user'] ?? '');
$pass = (string) ($_POST['db_pass'] ?? $_GET['db_pass'] ?? '');

if ($name === '' || $user === '' || $pass === '') {
    http_response_code(400);
    exit('Missing db_name, db_user or db_pass');
}

if (!is_dir($configDir) && !mkdir($configDir, 0700, true) && !is_dir($configDir)) {
    http_response_code(500);
    exit('Cannot create config directory');
}

$content = sprintf(
    <<<'PHP'
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

PHP,
    addslashes($host),
    addslashes($name),
    addslashes($user),
    addslashes($pass)
);

if (file_put_contents($configFile, $content, LOCK_EX) === false) {
    http_response_code(500);
    exit('Write failed');
}

@chmod($configFile, 0600);

try {
    require_once $configFile;
    $pdo = getDB();
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS leads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(255) NOT NULL,
  url_negocio VARCHAR(255) NOT NULL,
  presupuesto VARCHAR(80) NOT NULL DEFAULT '—',
  reto_principal TEXT NOT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_leads_creado (creado),
  INDEX idx_leads_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    $pdo->query('SELECT 1 FROM leads LIMIT 1');
} catch (Throwable $e) {
    @unlink($configFile);
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('DB test failed [' . $name . '/' . $user . ']: ' . $e->getMessage());
}

@unlink(__FILE__);
header('Content-Type: text/plain; charset=utf-8');
echo 'OK database.php installed';
