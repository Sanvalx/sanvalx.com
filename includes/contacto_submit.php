<?php
declare(strict_types=1);

/**
 * Procesamiento único del formulario de contacto (contacto.html).
 */
require_once __DIR__ . '/functions.php';

function contacto_is_ajax(): bool {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function contacto_respond_error(string $message, int $httpCode, string $redirectCode): void {
    if (contacto_is_ajax()) {
        json_response(['ok' => false, 'error' => $message], $httpCode);
    }
    http_response_code(303);
    header('Location: /contacto.html?e=' . rawurlencode($redirectCode));
    exit;
}

function contacto_respond_success(): void {
    if (contacto_is_ajax()) {
        json_response(['ok' => true, 'message' => 'Mensaje recibido correctamente.']);
    }
    header('Location: /gracias.html', true, 303);
    exit;
}

function contacto_get_pdo(): PDO {
    $databaseFile = __DIR__ . '/../config/database.php';
    if (is_file($databaseFile)) {
        require_once $databaseFile;
        return getDB();
    }

    $configPath = __DIR__ . '/../config.php';
    if (!is_file($configPath)) {
        error_log('contacto_submit: falta config/database.php o config.php.');
        contacto_respond_error('Configuración del servidor incompleta.', 503, 'config');
    }

    $config = require $configPath;
    foreach (['db_host', 'db_name', 'db_user', 'db_pass'] as $key) {
        if (!isset($config[$key]) || !is_string($config[$key])) {
            error_log('contacto_submit: falta la clave de configuración: ' . $key);
            contacto_respond_error('Configuración del servidor incompleta.', 503, 'config');
        }
    }

    return new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function contacto_get_webhook_url(): string {
    $configPath = __DIR__ . '/../config.php';
    if (!is_file($configPath)) {
        return '';
    }
    $config = require $configPath;
    $url = $config['make_webhook'] ?? '';
    return is_string($url) ? trim($url) : '';
}

function contacto_notify_make(
    string $webhookUrl,
    string $nombre,
    string $email,
    string $empresa,
    string $whatsapp,
    string $mensaje,
    string $ip
): void {
    if ($webhookUrl === '') {
        return;
    }

    $payload = json_encode([
        'nombre' => $nombre,
        'email' => $email,
        'empresa' => $empresa,
        'whatsapp' => $whatsapp,
        'mensaje' => $mensaje,
        'ip' => $ip,
        'fecha' => date('Y-m-d H:i:s'),
        'tipo' => 'Formulario contacto',
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($webhookUrl);
    if ($ch === false) {
        return;
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function contacto_process_submission(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        contacto_respond_error('Método no permitido.', 405, 'servidor');
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $rateKey = 'contact_' . md5((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    $now = time();
    if (!isset($_SESSION[$rateKey])) {
        $_SESSION[$rateKey] = [];
    }
    $_SESSION[$rateKey] = array_values(array_filter(
        $_SESSION[$rateKey],
        static fn(int $t): bool => $t > $now - 3600
    ));
    if (count($_SESSION[$rateKey]) >= 5) {
        contacto_respond_error('Demasiados envíos. Inténtalo más tarde.', 429, 'limite');
    }
    $_SESSION[$rateKey][] = $now;

    if (!empty($_POST['website_url'])) {
        contacto_respond_success();
    }

    $nombre = sanitize_input((string) ($_POST['nombre'] ?? ''), 120);
    $email = trim((string) ($_POST['email'] ?? ''));
    $empresa = sanitize_input((string) ($_POST['empresa'] ?? ''), 255);
    $whatsapp = sanitize_input((string) ($_POST['whatsapp'] ?? ''), 40);
    $mensaje = sanitize_input(
        (string) ($_POST['reto_principal'] ?? $_POST['mensaje'] ?? ''),
        2500
    );

    if (!isset($_POST['privacidad']) || (string) $_POST['privacidad'] !== '1') {
        contacto_respond_error('Debes aceptar la política de privacidad.', 400, 'privacidad');
    }

    if ($nombre === '' || $mensaje === '') {
        contacto_respond_error('Faltan campos obligatorios.', 400, 'validacion');
    }
    if (!is_valid_email($email)) {
        contacto_respond_error('El email no es válido.', 400, 'validacion');
    }

    $urlNegocio = $empresa !== '' ? $empresa : '—';
    $presupuesto = '—';
    $mensajeCompleto = $mensaje;
    if ($whatsapp !== '') {
        $mensajeCompleto .= "\n\nWhatsApp: " . $whatsapp;
    }

    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    try {
        $pdo = contacto_get_pdo();
        $sql = 'INSERT INTO leads (nombre, email, url_negocio, presupuesto, reto_principal, ip, creado)
                VALUES (?, ?, ?, ?, ?, ?, NOW())';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nombre,
            $email,
            $urlNegocio,
            $presupuesto,
            $mensajeCompleto,
            $ip !== '' ? $ip : null,
        ]);

        contacto_notify_make(
            contacto_get_webhook_url(),
            $nombre,
            $email,
            $empresa,
            $whatsapp,
            $mensaje,
            $ip
        );

        contacto_respond_success();
    } catch (Throwable $e) {
        error_log('contacto_submit: ' . $e->getMessage());
        contacto_respond_error('No se pudo enviar el formulario.', 500, 'servidor');
    }
}

contacto_process_submission();
