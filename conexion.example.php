<?php
// Plantilla de conexión. Cópiala como conexion.php y rellena tus datos.
// conexion.php está en .gitignore: las contraseñas nunca se suben a GitHub.
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=biblioteca;charset=utf8mb4', // servidor y base de datos
        'root',                                                    // usuario
        '',                                                        // contraseña
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Consultas preparadas reales en el servidor (no simuladas por PHP)
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    exit('No se ha podido conectar con la base de datos.');
}

require_once __DIR__ . '/funciones.php';
