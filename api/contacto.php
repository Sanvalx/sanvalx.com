<?php
/**
 * API contacto SANVALX: recibe formulario, guarda en MySQL y dispara automatización.
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../includes/functions.php';
require_post();

// 1. Evitar Spam
rate_limit_contact(5);

// 2. Recoger y Limpiar datos
$nombre  = isset($_POST['nombre'])  ? sanitize_input($_POST['nombre'], 120)  : '';
$email   = isset($_POST['email'])   ? trim($_POST['email'])                  : '';
$mensaje = isset($_POST['mensaje']) ? sanitize_input($_POST['mensaje'], 2000) : '';

// 3. Validaciones básicas
if ($nombre === '' || $email === '' || $mensaje === '') {
    json_response(['ok' => false, 'error' => 'Faltan campos obligatorios.'], 400);
}
if (!is_valid_email($email)) {
    json_response(['ok' => false, 'error' => 'Email no válido.'], 400);
}

try {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDB();

    // 4. Guardar en MySQL
    $sql = "INSERT INTO contactos (nombre, email, mensaje, ip, creado) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $guardado_sql = $stmt->execute([$nombre, $email, $mensaje, $ip]);

    // 5. ENVIAR A MAKE (Sólo si se guardó en BD correctamente)
    if ($guardado_sql) {
        
        // Pega aquí la URL que te dio el módulo Webhook de Make
        $webhook_url = "https://hook.us2.make.com/vx7jlrn64xhk1sbp1wad45gkbjm8kxf5"; 

        $payload = json_encode([
            "nombre"  => $nombre,
            "email"   => $email,
            "mensaje" => $mensaje,
            "ip"      => $ip,
            "fecha"   => date('Y-m-d H:i:s'),
            "tipo"    => "Contacto General"
        ]);

        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // No queremos que la web espere demasiado
        curl_exec($ch);
        curl_close($ch);
    }

    // 6. Respuesta final al usuario en la web
    json_response(['ok' => true, 'mensaje' => 'Mensaje recibido. Te responderemos pronto.']);

} catch (Throwable $e) {
    error_log('SANVALX contacto DB/Webhook: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Error de servidor. Intentelo más tarde.'], 500);
}