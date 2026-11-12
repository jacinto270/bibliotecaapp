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

-- Usuarios de la aplicación (la primera cuenta creada en crear_cuenta.php es la de administrador)
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

INSERT INTO libros (isbn, titulo, autor, anio, genero) VALUES
  ('978-84-0000-010-3', 'Don Quijote de la Mancha', 'Miguel de Cervantes', 1605, 'novela'),
  ('978-84-0000-011-0', '1984', 'George Orwell', 1949, 'novela'),
  ('978-84-0000-012-7', 'Orgullo y prejuicio', 'Jane Austen', 1813, 'novela'),
  ('978-84-0000-013-4', 'Crimen y castigo', 'Fiódor Dostoyevski', 1866, 'novela'),
  ('978-84-0000-014-1', 'La casa de los espíritus', 'Isabel Allende', 1982, 'novela'),
  ('978-84-0000-015-8', 'Rayuela', 'Julio Cortázar', 1963, 'novela'),
  ('978-84-0000-016-5', 'El nombre de la rosa', 'Umberto Eco', 1980, 'novela'),
  ('978-84-0000-017-2', 'Nada', 'Carmen Laforet', 1945, 'novela'),
  ('978-84-0000-018-9', 'Veinte poemas de amor y una canción desesperada', 'Pablo Neruda', 1924, 'poesía'),
  ('978-84-0000-019-6', 'Campos de Castilla', 'Antonio Machado', 1912, 'poesía'),
  ('978-84-0000-020-2', 'Rimas', 'Gustavo Adolfo Bécquer', 1871, 'poesía'),
  ('978-84-0000-021-9', 'Poeta en Nueva York', 'Federico García Lorca', 1940, 'poesía'),
  ('978-84-0000-022-6', 'Hojas de hierba', 'Walt Whitman', 1855, 'poesía'),
  ('978-84-0000-023-3', 'Platero y yo', 'Juan Ramón Jiménez', 1914, 'poesía'),
  ('978-84-0000-024-0', 'El mundo de Sofía', 'Jostein Gaarder', 1991, 'ensayo'),
  ('978-84-0000-025-7', 'El arte de la guerra', 'Sun Tzu', NULL, 'ensayo'),
  ('978-84-0000-026-4', 'Pensar rápido, pensar despacio', 'Daniel Kahneman', 2011, 'ensayo'),
  ('978-84-0000-027-1', 'El hombre en busca de sentido', 'Viktor Frankl', 1946, 'ensayo'),
  ('978-84-0000-028-8', 'Una habitación propia', 'Virginia Woolf', 1929, 'ensayo'),
  ('978-84-0000-029-5', 'La rebelión de las masas', 'José Ortega y Gasset', 1930, 'ensayo'),
  ('978-84-0000-030-1', 'Matilda', 'Roald Dahl', 1988, 'infantil'),
  ('978-84-0000-031-8', 'Charlie y la fábrica de chocolate', 'Roald Dahl', 1964, 'infantil'),
  ('978-84-0000-032-5', 'Harry Potter y la piedra filosofal', 'J. K. Rowling', 1997, 'infantil'),
  ('978-84-0000-033-2', 'La historia interminable', 'Michael Ende', 1979, 'infantil'),
  ('978-84-0000-034-9', 'Manolito Gafotas', 'Elvira Lindo', 1994, 'infantil'),
  ('978-84-0000-035-6', 'Alicia en el país de las maravillas', 'Lewis Carroll', 1865, 'infantil'),
  ('978-84-0000-036-3', 'El pequeño Nicolás', 'René Goscinny', 1959, 'infantil'),
  ('978-84-0000-037-0', 'La vida es sueño', 'Pedro Calderón de la Barca', 1635, 'teatro'),
  ('978-84-0000-038-7', 'Hamlet', 'William Shakespeare', 1603, 'teatro'),
  ('978-84-0000-039-4', 'La casa de Bernarda Alba', 'Federico García Lorca', 1945, 'teatro'),
  ('978-84-0000-040-0', 'Bodas de sangre', 'Federico García Lorca', 1933, 'teatro'),
  ('978-84-0000-041-7', 'Don Juan Tenorio', 'José Zorrilla', 1844, 'teatro'),
  ('978-84-0000-042-4', 'Esperando a Godot', 'Samuel Beckett', 1952, 'teatro'),
  ('978-84-0000-043-1', 'Maus', 'Art Spiegelman', 1991, 'cómic'),
  ('978-84-0000-044-8', 'Persépolis', 'Marjane Satrapi', 2000, 'cómic'),
  ('978-84-0000-045-5', 'Watchmen', 'Alan Moore', 1987, 'cómic'),
  ('978-84-0000-046-2', 'Mafalda. Todas las tiras', 'Quino', 1993, 'cómic'),
  ('978-84-0000-047-9', 'Astérix el galo', 'René Goscinny', 1961, 'cómic'),
  ('978-84-0000-048-6', 'Arrugas', 'Paco Roca', 2007, 'cómic'),
  ('978-84-0000-049-3', 'SPQR. Una historia de la antigua Roma', 'Mary Beard', 2015, 'historia'),
  ('978-84-0000-050-9', 'La guerra civil española', 'Hugh Thomas', 1961, 'historia'),
  ('978-84-0000-051-6', 'Armas, gérmenes y acero', 'Jared Diamond', 1997, 'historia'),
  ('978-84-0000-052-3', 'El infinito en un junco', 'Irene Vallejo', 2019, 'historia'),
  ('978-84-0000-053-0', 'Diario de Ana Frank', 'Ana Frank', 1947, 'historia'),
  ('978-84-0000-054-7', 'Postguerra. Una historia de Europa desde 1945', 'Tony Judt', 2005, 'historia'),
  ('978-84-0000-055-4', 'El origen de las especies', 'Charles Darwin', 1859, 'ciencia'),
  ('978-84-0000-056-1', 'Cosmos', 'Carl Sagan', 1980, 'ciencia'),
  ('978-84-0000-057-8', 'El gen egoísta', 'Richard Dawkins', 1976, 'ciencia'),
  ('978-84-0000-058-5', 'Primavera silenciosa', 'Rachel Carson', 1962, 'ciencia'),
  ('978-84-0000-059-2', 'El universo elegante', 'Brian Greene', 1999, 'ciencia');

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
