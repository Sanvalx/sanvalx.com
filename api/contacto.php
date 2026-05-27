<?php
declare(strict_types=1);

/**
 * Compatibilidad API: mismo procesamiento que el formulario de contacto.html.
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../includes/contacto_submit.php';
