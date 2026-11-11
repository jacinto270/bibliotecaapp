<?php
require 'conexion.php';

// Solo acepta POST con token CSRF (un enlace o una web ajena no pueden borrar libros)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('libros.php');
}
comprobar_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$accion = $_POST['accion'] ?? '';

if (!$id) {
    redirigir('libros.php');
}

if ($accion === 'recuperar') {
    $recuperar = $pdo->prepare('UPDATE libros SET activo = 1 WHERE id = ? AND activo = 0');
    $recuperar->execute([$id]);
    flash('aviso', $recuperar->rowCount() ? 'El libro vuelve a estar en el catálogo.' : 'No se ha podido recuperar el libro.');
    redirigir('libros.php?ver=bajas');
}

try {
    $pdo->beginTransaction();

    $libro = $pdo->prepare('SELECT titulo FROM libros WHERE id = ? AND activo = 1 FOR UPDATE');
    $libro->execute([$id]);
    $tituloLibro = $libro->fetchColumn();

    $prestamos = $pdo->prepare(
        'SELECT COUNT(*) AS total, SUM(fecha_devolucion IS NULL) AS en_curso
         FROM prestamos WHERE libro_id = ?'
    );
    $prestamos->execute([$id]);
    $cuenta = $prestamos->fetch();

    if ($tituloLibro === false) {
        $pdo->rollBack();
        flash('error', 'Ese libro no existe o ya estaba dado de baja.');
    } elseif ($cuenta['en_curso'] > 0) {
        $pdo->rollBack();
        flash('error', 'No se puede quitar «' . $tituloLibro . '»: está prestado. Espera a que lo devuelvan.');
    } elseif ($cuenta['total'] == 0) {
        // Nunca se ha prestado: se borra del todo
        $pdo->prepare('DELETE FROM libros WHERE id = ?')->execute([$id]);
        $pdo->commit();
        flash('aviso', '«' . $tituloLibro . '» se ha eliminado del catálogo.');
    } else {
        // Tiene historial: se da de baja para no perder los préstamos antiguos
        $pdo->prepare('UPDATE libros SET activo = 0 WHERE id = ?')->execute([$id]);
        $pdo->commit();
        flash('aviso', '«' . $tituloLibro . '» se ha dado de baja. Su historial de préstamos se conserva.');
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'No se ha podido quitar el libro.');
}

redirigir('libros.php');
