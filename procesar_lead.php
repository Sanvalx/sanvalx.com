<?php
declare(strict_types=1);

// Configuración DB (Hostinger)
$DB_HOST = 'localhost';
$DB_NAME = 'u636621141_9TsyO';
$DB_USER = 'u636621141_Tg6BM';
$DB_PASS = 'TU_PASSWORD';

// Webhook (Make)
$WEBHOOK_URL = 'https://hook.eu1.make.com/...';

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Método no permitido'], 405);
}

// Validación Honeypot: Si el campo oculto tiene datos, es un bot.
if (!empty($_POST['website_url'])) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        json_response(['ok' => true, 'message' => 'Lead guardado correctamente']);
    }
    header('Location: /gracias.html');
    exit;
}

$nombre = sanitize($_POST['nombre'] ?? '', 120);
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '';
$urlNegocio = sanitize($_POST['url_negocio'] ?? '', 255);
$presupuesto = sanitize($_POST['presupuesto'] ?? '', 80);
$reto = sanitize($_POST['reto_principal'] ?? '', 2500);

if ($nombre === '' || $email === '' || $urlNegocio === '' || $presupuesto === '' || $reto === '') {
    json_response(['ok' => false, 'error' => 'Faltan campos obligatorios'], 400);
}

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $sql = "INSERT INTO leads (nombre, email, url_negocio, presupuesto, reto_principal, ip, creado) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $nombre,
        $email,
        $urlNegocio,
        $presupuesto,
        $reto,
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    $payload = [
        'nombre' => $nombre,
        'email' => $email,
        'url_negocio' => $urlNegocio,
        'presupuesto' => $presupuesto,
        'reto_principal' => $reto,
        'fecha' => date('c')
    ];

    // Envío al webhook (no bloqueante de forma estricta)
    $ch = curl_init($WEBHOOK_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    curl_exec($ch);
    curl_close($ch);

    // Si viene por fetch/XHR devolvemos JSON, si no redirigimos
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        json_response(['ok' => true, 'message' => 'Lead guardado correctamente']);
    }
    header('Location: /gracias.html');
    exit;
} catch (Throwable $e) {
    error_log('Error procesar_lead.php: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'No se pudo procesar el lead'], 500);
}
