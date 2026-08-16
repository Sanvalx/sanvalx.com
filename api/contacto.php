<?php
declare(strict_types=1);

/**
 * Compatibilidad API: mismo procesamiento que el formulario de contacto.html.
 * Solo acepta peticiones del mismo origen (sanvalx.com).
 */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
$allowedOrigins = ['https://sanvalx.com', 'https://www.sanvalx.com'];
if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
}

require_once __DIR__ . '/../includes/contacto_submit.php';
contacto_process_submission();
