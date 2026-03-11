-- SANVALX: esquema MySQL para la web
-- Ejecutar en phpMyAdmin (Hostinger) una vez creada la base de datos.
-- Sustituir TU_NOMBRE_DE_BASE_DE_DATOS por el nombre real si es necesario.

-- Tabla de contactos (formulario / leads)
CREATE TABLE IF NOT EXISTS contactos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(255) NOT NULL,
  mensaje TEXT NOT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_creado (creado),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
