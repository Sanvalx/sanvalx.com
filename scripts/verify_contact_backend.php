<?php
declare(strict_types=1);

/**
 * Verificación del backend del formulario de contacto.
 * Ejecutar en el servidor: php scripts/verify_contact_backend.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo CLI.');
}

$root = dirname(__DIR__);
$errors = [];
$checks = [];

function check(string $label, bool $ok, string $detail = ''): void {
    global $checks, $errors;
    $checks[] = ['label' => $label, 'ok' => $ok, 'detail' => $detail];
    if (!$ok) {
        $errors[] = $label . ($detail !== '' ? ': ' . $detail : '');
    }
}

$databaseFile = $root . '/config/database.php';
$configFile = $root . '/config.php';
$schemaFile = $root . '/database/schema.sql';
$submitFile = $root . '/includes/contacto_submit.php';
$processorFile = $root . '/procesar_lead.php';

check('procesar_lead.php existe', is_file($processorFile));
check('contacto.php existe', is_file($root . '/contacto.php'));
check('includes/contacto_submit.php existe', is_file($submitFile));
check('includes/functions.php existe', is_file($root . '/includes/functions.php'));
check('database/schema.sql existe', is_file($schemaFile));

$rateLimitDir = $root . '/storage/rate_limit';
if (!is_dir($rateLimitDir)) {
    @mkdir($rateLimitDir, 0700, true);
}
check('storage/rate_limit escribible', is_dir($rateLimitDir) && is_writable($rateLimitDir));

$configSource = '';
$pdo = null;

if (is_file($databaseFile)) {
    require_once $databaseFile;
    if (!function_exists('getDB')) {
        check('config/database.php define getDB()', false);
    } else {
        $configSource = 'config/database.php';
        try {
            $pdo = getDB();
            check('Conexión MySQL (database.php)', true);
        } catch (Throwable $e) {
            check('Conexión MySQL (database.php)', false, $e->getMessage());
        }
    }
} elseif (is_file($configFile)) {
    $config = require $configFile;
    $configSource = 'config.php';
    $required = ['db_host', 'db_name', 'db_user', 'db_pass'];
    $missing = array_filter($required, static function (string $k) use ($config): bool {
        return empty($config[$k]);
    });
    check('config.php tiene credenciales', $missing === [], $missing ? 'faltan: ' . implode(', ', $missing) : '');
    if ($missing === []) {
        try {
            $pdo = new PDO(
                'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
                $config['db_user'],
                $config['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            check('Conexión MySQL (config.php)', true);
        } catch (Throwable $e) {
            check('Conexión MySQL (config.php)', false, $e->getMessage());
        }
    }
} else {
    check('Configuración BD presente', false, 'Falta config/database.php o config.php');
}

if ($pdo instanceof PDO) {
    $hasTable = false;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'leads'");
        $hasTable = $stmt !== false && $stmt->fetchColumn() !== false;
        check('Tabla leads existe', $hasTable, $hasTable ? '' : 'Ejecuta database/schema.sql en phpMyAdmin');
    } catch (Throwable $e) {
        check('Tabla leads existe', false, $e->getMessage());
    }

    if ($hasTable) {
        try {
            $cols = $pdo->query('SHOW COLUMNS FROM leads')->fetchAll(PDO::FETCH_COLUMN);
            $requiredCols = ['nombre', 'email', 'url_negocio', 'presupuesto', 'reto_principal', 'ip', 'creado'];
            $missingCols = array_diff($requiredCols, $cols);
            check('Columnas leads correctas', $missingCols === [], $missingCols ? 'faltan: ' . implode(', ', $missingCols) : '');
        } catch (Throwable $e) {
            check('Columnas leads correctas', false, $e->getMessage());
        }
    }
}

echo "SANVALX — verificación backend contacto\n";
echo str_repeat('-', 42) . "\n";
if ($configSource !== '') {
    echo "Origen config: {$configSource}\n";
}
foreach ($checks as $c) {
    $mark = $c['ok'] ? '[OK]' : '[FAIL]';
    echo "{$mark} {$c['label']}";
    if ($c['detail'] !== '') {
        echo " — {$c['detail']}";
    }
    echo "\n";
}
echo str_repeat('-', 42) . "\n";

if ($errors === []) {
    echo "Todo correcto. Prueba el formulario en /contacto.html → debe redirigir a /gracias.html\n";
    exit(0);
}

echo count($errors) . " error(es). Corrige antes de probar el formulario en producción.\n";
exit(1);
