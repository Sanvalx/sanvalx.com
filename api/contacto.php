<?php
/**
 * API contacto: recibe formulario, valida, guarda en MySQL.
 * Uso: POST con nombre, email, mensaje (y opcional asunto).
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../includes/functions.php';
require_post();

// Rate limit para evitar spam
rate_limit_contact(5);

$nombre  = isset($_POST['nombre'])  ? sanitize_input($_POST['nombre'], 120)  : '';
$email   = isset($_POST['email'])   ? trim($_POST['email'])                  : '';
$mensaje = isset($_POST['mensaje']) ? sanitize_input($_POST['mensaje'], 2000) : '';

if ($nombre === '' || $email === '' || $mensaje === '') {
    json_response(['ok' => false, 'error' => 'Faltan campos obligatorios.'], 400);
}
if (!is_valid_email($email)) {
    json_response(['ok' => false, 'error' => 'Email no válido.'], 400);
}

try {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDB();
} catch (Throwable $e) {
    error_log('SANVALX contacto DB: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Error de configuración. Intente más tarde.'], 500);
}

$sql = "INSERT INTO contactos (nombre, email, mensaje, ip, creado) VALUES (?, ?, ?, ?, NOW())";
$stmt = $pdo->prepare($sql);
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$stmt->execute([$nombre, $email, $mensaje, $ip]);

json_response(['ok' => true, 'mensaje' => 'Mensaje recibido. Te responderemos pronto.']);
