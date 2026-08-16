<?php
declare(strict_types=1);

/**
 * Funciones de seguridad y utilidad para SANVALX.
 */

/** Sanitiza string para salida HTML */
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Sanitiza y recorta para uso en BD (evita inyección y exceso de longitud) */
function sanitize_input(string $s, int $maxLen = 500): string {
    $s = trim($s);
    $s = strip_tags($s);
    return mb_substr($s, 0, $maxLen);
}

/** Valida email */
function is_valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Respuesta JSON para API */
function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Comprueba que la petición sea POST (para formularios) */
function require_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['ok' => false, 'error' => 'Método no permitido'], 405);
    }
}

/** IP del cliente (solo REMOTE_ADDR; no confiar en cabeceras X-Forwarded sin proxy configurado) */
function client_ip(): string {
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/** Inicia sesión con cookies seguras */
function secure_session_start(): void {
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/** Genera o devuelve el token CSRF de la sesión actual */
function csrf_token(): string {
    secure_session_start();
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Valida el token CSRF enviado en POST */
function csrf_validate(?string $token): bool {
    secure_session_start();
    if ($token === null || $token === '' || empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate limit por IP en disco (no depende de cookies de sesión).
 * @return true si la petición está permitida
 */
function rate_limit_by_ip(string $bucket, int $maxPerHour = 5, int $windowSeconds = 3600): bool {
    $dir = dirname(__DIR__) . '/storage/rate_limit';
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        error_log('rate_limit_by_ip: no se pudo crear ' . $dir);
        return true;
    }

    $hash = hash('sha256', client_ip());
    $file = $dir . '/' . preg_replace('/[^a-z0-9_-]/i', '', $bucket) . '_' . $hash . '.json';
    $now = time();
    $timestamps = [];

    if (is_file($file)) {
        $raw = file_get_contents($file);
        $decoded = json_decode($raw ?: '[]', true);
        if (is_array($decoded)) {
            $timestamps = array_values(array_filter(
                $decoded,
                static fn($t): bool => is_int($t) && $t > $now - $windowSeconds
            ));
        }
    }

    if (count($timestamps) >= $maxPerHour) {
        return false;
    }

    $timestamps[] = $now;
    file_put_contents($file, json_encode($timestamps), LOCK_EX);

    return true;
}
