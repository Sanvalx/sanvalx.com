<?php
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

/** Rate limit simple por IP (evita spam): máx 5 envíos por hora por IP */
function rate_limit_contact(int $maxPerHour = 5): void {
    $key = 'contact_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $now = time();
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    $_SESSION[$key] = array_filter($_SESSION[$key], fn($t) => $t > $now - 3600);
    if (count($_SESSION[$key]) >= $maxPerHour) {
        json_response(['ok' => false, 'error' => 'Demasiados envíos. Intente más tarde.'], 429);
    }
    $_SESSION[$key][] = $now;
}
