CONFIGURACIÓN BASE DE DATOS (SANVALX)
=====================================

Formulario único: /contacto.html → /procesar_lead.php

1. En Hostinger (hPanel):
   - Bases de datos MySQL → Crear base de datos.
   - Anota: nombre de la BD, usuario MySQL y contraseña.

2. En phpMyAdmin:
   - Importa o ejecuta: database/schema.sql
   - Crea la tabla "leads" para las solicitudes de contacto.

3. En este directorio, edita: database.php
   - DB_NAME = nombre de tu base de datos
   - DB_USER = usuario MySQL
   - DB_PASS = contraseña MySQL

   Alternativa: config.php en la raíz (copia de config.example.php).

4. Webhook Make (opcional):
   - Añade make_webhook en config.php con la URL del módulo Webhook.

5. No subas database.php con datos reales a repositorios públicos.
   El archivo .htaccess impide el acceso web a /config/.
