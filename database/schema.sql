-- SANVALX: esquema MySQL para el formulario de contacto
-- Ejecutar en phpMyAdmin (Hostinger) una vez creada la base de datos.

-- Tabla única de solicitudes (contacto.html → procesar_lead.php)
CREATE TABLE IF NOT EXISTS leads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(255) NOT NULL,
  url_negocio VARCHAR(255) NOT NULL COMMENT 'Empresa o proyecto; "—" si no se indica',
  presupuesto VARCHAR(80) NOT NULL DEFAULT '—',
  reto_principal TEXT NOT NULL COMMENT 'Mensaje del visitante',
  ip VARCHAR(45) DEFAULT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_leads_creado (creado),
  INDEX idx_leads_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
