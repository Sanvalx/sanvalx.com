<?php
declare(strict_types=1);

function sanitize(string $v, int $max = 500): string {
    $v = trim(strip_tags($v));
    return mb_substr($v, 0, $max);
}

function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function is_ajax_request(): bool {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function respond_error(string $message, int $httpCode, string $redirectCode): void {
    if (is_ajax_request()) {
        json_response(['ok' => false, 'error' => $message], $httpCode);
    }
    http_response_code(303);
    header('Location: /contacto.html?e=' . rawurlencode($redirectCode));
    exit;
}

$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
    error_log('procesar_lead.php: falta config.php (copia config.example.php).');
    respond_error('Configuración del servidor incompleta.', 503, 'config');
}

$config = require $configPath;

foreach (['db_host', 'db_name', 'db_user', 'db_pass'] as $key) {
    if (!isset($config[$key]) || !is_string($config[$key])) {
        error_log('procesar_lead.php: falta la clave de configuración: ' . $key);
        respond_error('Configuración del servidor incompleta.', 503, 'config');
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_error('Método no permitido', 405, 'servidor');
}

if (!empty($_POST['website_url'])) {
    if (is_ajax_request()) {
        json_response(['ok' => true, 'message' => 'Lead guardado correctamente']);
    }
    header('Location: /gracias.html', true, 303);
    exit;
}

$nombre = sanitize($_POST['nombre'] ?? '', 120);
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '';
$empresa = sanitize($_POST['empresa'] ?? '', 255);
$whatsapp = sanitize($_POST['whatsapp'] ?? '', 40);
$reto = sanitize($_POST['reto_principal'] ?? '', 2500);

if (!isset($_POST['privacidad']) || $_POST['privacidad'] !== '1') {
    respond_error('Debes aceptar la política de privacidad.', 400, 'privacidad');
}

if ($nombre === '' || $email === '' || $reto === '') {
    respond_error('Faltan campos obligatorios.', 400, 'validacion');
}

$urlNegocio = $empresa !== '' ? $empresa : '—';
$presupuesto = '—';
if ($whatsapp !== '') {
    $reto .= "\n\nWhatsApp: " . $whatsapp;
}

$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $sql = 'INSERT INTO leads (nombre, email, url_negocio, presupuesto, reto_principal, ip, creado) VALUES (?, ?, ?, ?, ?, ?, NOW())';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $nombre,
        $email,
        $urlNegocio,
        $presupuesto,
        $reto,
        $remoteAddr !== '' ? $remoteAddr : null,
    ]);

    if (is_ajax_request()) {
        json_response(['ok' => true, 'message' => 'Lead guardado correctamente']);
    }
    header('Location: /gracias.html', true, 303);
    exit;
} catch (Throwable $e) {
    error_log('Error procesar_lead.php: ' . $e->getMessage());
    respond_error('No se pudo procesar el lead.', 500, 'servidor');
}
