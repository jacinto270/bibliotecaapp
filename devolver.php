<?php
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprobar_csrf();

    // Id del préstamo como número entero
    $id = (int) ($_POST['id'] ?? 0);

    // Pone la fecha de hoy en fecha_devolucion, solo si el préstamo sigue sin devolver
    $sql = 'UPDATE prestamos SET fecha_devolucion = CURRENT_DATE WHERE id = ? AND fecha_devolucion IS NULL';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        flash('aviso', 'Libro devuelto correctamente.');
    } else {
        flash('error', 'Ese préstamo no existe o ya estaba devuelto.');
    }
}

redirigir('index.php');
