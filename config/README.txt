CONFIGURACIÓN BASE DE DATOS (SANVALX)
=====================================

1. En Hostinger (hPanel):
   - Bases de datos MySQL → Crear base de datos.
   - Anota: nombre de la BD, usuario MySQL y contraseña.

2. En phpMyAdmin:
   - Importa o ejecuta el contenido de: database/schema.sql
   (crea la tabla "contactos").

3. En este directorio, edita: database.php
   - DB_NAME = nombre de tu base de datos
   - DB_USER = usuario MySQL
   - DB_PASS = contraseña MySQL

4. No subas database.php con datos reales a repositorios públicos.
   El archivo .htaccess ya impide que se acceda a /config/ por la web.
