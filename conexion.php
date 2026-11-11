<?php
// Conexión con la base de datos: la incluyen todas las páginas
try {
    $pdo = new PDO(
        // 'mysql:host=localhost;dbname=biblioteca;charset=utf8mb4',
        // 'root',
        // '',
        'mysql:host=sql101.infinityfree.com;dbname=if0_43416809_appbiblioteca;charset=utf8mb4',
        'if0_43416809',
        'wge9Xue11n7tIA',
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
