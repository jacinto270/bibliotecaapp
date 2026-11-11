-- Biblioteca municipal: libros, lectores y préstamos (práctica de diseño PD1)
-- IFCD0210 · UF1845 · Base de datos para MySQL 8.4 (WAMP)
-- Importar en phpMyAdmin: pestaña «Importar», elegir este archivo y pulsar «Importar».
-- Crea la base de datos `biblioteca` (si ya existía, la borra y la vuelve a crear).

DROP DATABASE IF EXISTS biblioteca;
CREATE DATABASE biblioteca CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE biblioteca;

CREATE TABLE libros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  isbn VARCHAR(17) NOT NULL UNIQUE,
  titulo VARCHAR(100) NOT NULL,
  autor VARCHAR(80) NOT NULL,
  anio INT,
  genero VARCHAR(20) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE lectores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(40) NOT NULL,
  apellidos VARCHAR(60) NOT NULL,
  dni VARCHAR(9) UNIQUE,
  telefono VARCHAR(15) NOT NULL,
  email VARCHAR(100) UNIQUE,
  fecha_alta DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE prestamos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  libro_id INT NOT NULL,
  lector_id INT NOT NULL,
  fecha_prestamo DATE NOT NULL,
  fecha_devolucion DATE,
  FOREIGN KEY (libro_id) REFERENCES libros(id),
  FOREIGN KEY (lector_id) REFERENCES lectores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO libros (id, isbn, titulo, autor, anio, genero) VALUES
  (1, '978-84-0000-001-1', 'Cien años de soledad', 'Gabriel García Márquez', 1967, 'novela'),
  (2, '978-84-0000-002-8', 'El viejo y el mar', 'Ernest Hemingway', 1952, 'novela'),
  (3, '978-84-0000-003-5', 'Romancero gitano', 'Federico García Lorca', 1928, 'poesía'),
  (4, '978-84-0000-004-2', 'Breve historia del tiempo', 'Stephen Hawking', 1988, 'ensayo'),
  (5, '978-84-0000-005-9', 'El principito', 'Antoine de Saint-Exupéry', 1943, 'infantil'),
  (6, '978-84-0000-006-6', 'La sombra del viento', 'Carlos Ruiz Zafón', 2001, 'novela'),
  (7, '978-84-0000-007-3', 'Marina', 'Carlos Ruiz Zafón', 1999, 'novela'),
  (8, '978-84-0000-008-0', 'Sapiens. De animales a dioses', 'Yuval Noah Harari', 2011, 'ensayo'),
  (9, '978-84-0000-009-7', 'Donde viven los monstruos', 'Maurice Sendak', 1963, 'infantil');

INSERT INTO lectores (id, nombre, apellidos, telefono, email, fecha_alta) VALUES
  (1, 'Rosa', 'Jiménez Castro', '600111222', 'rosa@correo.es', '2023-02-01'),
  (2, 'Manuel', 'Ortiz Serrano', '600222333', NULL, '2023-05-17'),
  (3, 'Inés', 'Navarro Prieto', '600333444', 'ines@correo.es', '2024-01-09'),
  (4, 'Daniel', 'Romero Vega', '600444555', 'daniel@correo.es', '2024-09-30'),
  (5, 'Sofía', 'Molina Ruiz', '600555666', 'sofia@correo.es', '2025-03-12'),
  (6, 'Alberto', 'Gil Campos', '600666777', NULL, '2026-10-02');

INSERT INTO prestamos (id, libro_id, lector_id, fecha_prestamo, fecha_devolucion) VALUES
  (1, 1, 1, '2026-09-01', '2026-09-15'),
  (2, 5, 3, '2026-09-03', '2026-09-10'),
  (3, 2, 2, '2026-09-05', '2026-09-20'),
  (4, 5, 4, '2026-09-12', '2026-09-26'),
  (5, 6, 1, '2026-09-16', '2026-10-01'),
  (6, 3, 3, '2026-09-20', '2026-10-04'),
  (7, 6, 5, '2026-10-02', '2026-10-16'),
  (8, 5, 1, '2026-10-05', '2026-10-19'),
  (9, 6, 2, '2026-10-20', NULL),
  (10, 4, 3, '2026-10-22', NULL),
  (11, 1, 5, '2026-10-28', NULL),
  (12, 5, 3, '2026-11-02', NULL);
