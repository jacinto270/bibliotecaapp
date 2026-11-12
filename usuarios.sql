-- Usuarios de la aplicación e intentos de acceso (para frenar a los robots)
-- Importar en phpMyAdmin con la base de datos de la aplicación seleccionada: NO borra nada.
-- Después, entra en crear_cuenta.php: la PRIMERA cuenta que se crea es la de administrador.

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(30) NOT NULL UNIQUE,
  nombre VARCHAR(60) NOT NULL,
  clave VARCHAR(255) NOT NULL,            -- hash de password_hash(), nunca la contraseña
  rol VARCHAR(15) NOT NULL DEFAULT 'bibliotecario',
  activo TINYINT(1) NOT NULL DEFAULT 0,   -- las cuentas nuevas esperan la aprobación de un admin
  creado DATETIME NOT NULL,
  ultimo_acceso DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE IF NOT EXISTS intentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo VARCHAR(10) NOT NULL,              -- 'entrar' (contraseña fallida) o 'alta' (cuenta pedida)
  ip VARCHAR(45) NOT NULL,
  usuario VARCHAR(30) NOT NULL DEFAULT '',
  momento DATETIME NOT NULL,
  INDEX (tipo, momento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
