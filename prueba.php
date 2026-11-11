<?php
require 'conexion.php';

$total = $pdo->query('SELECT COUNT(*) FROM libros')->fetchColumn();
echo "Conexión correcta: hay $total libros.";