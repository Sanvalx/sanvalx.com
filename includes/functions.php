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
    if (function_exists('mb_substr')) {
        return mb_substr($s, 0, $maxLen);
    }
    return substr($s, 0, $maxLen);
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

const CSRF_COOKIE_NAME = 'svx_csrf';

/** Petición servida por HTTPS */
function request_is_secure(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
}

/** Establece la cookie del token CSRF (httponly, sin depender de sesiones PHP) */
function csrf_set_cookie(string $token): void {
    $expires = time() + 7200;
    $secure = request_is_secure();

    if (PHP_VERSION_ID >= 70300) {
        setcookie(CSRF_COOKIE_NAME, $token, [
            'expires' => $expires,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        return;
    }

    setcookie(CSRF_COOKIE_NAME, $token, $expires, '/', '', $secure, true);
}

/** Genera o reutiliza el token CSRF (cookie + campo oculto deben coincidir al enviar) */
function csrf_token(): string {
    $cookie = $_COOKIE[CSRF_COOKIE_NAME] ?? '';
    if (is_string($cookie) && preg_match('/^[a-f0-9]{64}$/', $cookie) === 1) {
        return $cookie;
    }

    $token = bin2hex(random_bytes(32));
    csrf_set_cookie($token);
    return $token;
}

/** Valida el token CSRF enviado en POST */
function csrf_validate(?string $token): bool {
    if ($token === null || $token === '' || preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
        return false;
    }

    $cookie = $_COOKIE[CSRF_COOKIE_NAME] ?? '';
    if (!is_string($cookie) || $cookie === '') {
        return false;
    }

    return hash_equals($cookie, $token);
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
                static function ($t) use ($now, $windowSeconds): bool {
                    return is_int($t) && $t > $now - $windowSeconds;
                }
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
