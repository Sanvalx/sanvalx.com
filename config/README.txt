CONFIGURACIÓN BASE DE DATOS (SANVALX)
=====================================

Formulario único: /contacto.html → /procesar_lead.php
(La URL contacto.html se sirve vía contacto.php con token CSRF.)

1. En Hostinger (hPanel):
   - Bases de datos MySQL → Crear base de datos.
   - Anota: nombre de la BD, usuario MySQL y contraseña.

2. En phpMyAdmin:
   - Importa o ejecuta: database/schema.sql
   - Crea la tabla "leads" para las solicitudes de contacto.

3. En este directorio:
   - Copia database.example.php como database.php
   - Rellena DB_NAME, DB_USER y DB_PASS con tus credenciales reales
   - database.php NO debe subirse a git (está en .gitignore)

   Alternativa: config.php en la raíz (copia de config.example.php).

4. Webhook Make (opcional):
   - Añade make_webhook en config.php con la URL del módulo Webhook.

5. IMPORTANTE — credenciales expuestas:
   Si database.php estuvo en un repo público, rota la contraseña MySQL
   en hPanel antes de volver a desplegar.

6. Verificación en servidor (SSH o terminal Hostinger):
   php scripts/verify_contact_backend.php
   Debe mostrar [OK] en conexión MySQL y tabla leads.

7. Prueba manual del flujo:
   - Abre https://sanvalx.com/contacto.html
   - Envía el formulario con datos de prueba
   - Debes llegar a https://sanvalx.com/gracias.html
   - Si falla: ?e=config (falta config), ?e=servidor (error BD), ?e=validacion,
     ?e=csrf (recarga la página), ?e=limite (demasiados envíos)

8. Permisos en servidor:
   - storage/rate_limit/ debe ser escribible por PHP (chmod 700 recomendado)
