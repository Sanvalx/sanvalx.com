<?php
declare(strict_types=1);

// Copia como config.php en el servidor (config.php no va a git).
return [
    'db_host' => 'localhost',
    'db_name' => 'TU_BASE_DE_DATOS',
    'db_user' => 'TU_USUARIO_MYSQL',
    'db_pass' => 'TU_CONTRASEÑA_MYSQL',
    // Opcional: webhook Make para automatización al enviar el formulario
    'make_webhook' => '',
];
