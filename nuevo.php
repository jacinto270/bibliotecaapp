<?php
require 'conexion.php';

$error = '';

// Lector preseleccionado (nuevo.php?lector=3, desde su ficha)
$lectorId = filter_input(INPUT_GET, 'lector', FILTER_VALIDATE_INT) ?: null;
$libroId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprobar_csrf();

    $lectorId = filter_input(INPUT_POST, 'lector_id', FILTER_VALIDATE_INT);
    $libroId = filter_input(INPUT_POST, 'libro_id', FILTER_VALIDATE_INT);

    if (!$lectorId || !$libroId) {
        $error = 'Elige un lector y un libro.';
    } else {
        try {
            $pdo->beginTransaction();

            // Bloquea el libro y el lector hasta el commit o el rollBack:
            // dos peticiones a la vez no pueden prestar el mismo libro
            $libro = $pdo->prepare('SELECT activo FROM libros WHERE id = ? FOR UPDATE');
            $libro->execute([$libroId]);
            $activo = $libro->fetchColumn();

            $lector = $pdo->prepare('SELECT id FROM lectores WHERE id = ? FOR UPDATE');
            $lector->execute([$lectorId]);
            $lectorExiste = (bool) $lector->fetchColumn();

            // ¿Tiene ese libro un préstamo sin devolver?
            $prestado = $pdo->prepare(
                'SELECT COUNT(*) FROM prestamos WHERE libro_id = ? AND fecha_devolucion IS NULL'
            );
            $prestado->execute([$libroId]);

            // ¿Cuántos libros tiene ya el lector? ¿Alguno con retraso?
            $delLector = $pdo->prepare(
                'SELECT COUNT(*) AS total,
                        COALESCE(SUM(DATEDIFF(CURDATE(), fecha_prestamo) > ?), 0) AS retrasados
                 FROM prestamos WHERE lector_id = ? AND fecha_devolucion IS NULL'
            );
            $delLector->execute([PLAZO_DIAS, $lectorId]);
            $actuales = $delLector->fetch();

            if (!$activo) {
                $error = 'Ese libro no existe o está dado de baja.';
            } elseif (!$lectorExiste) {
                $error = 'Ese lector no existe.';
            } elseif ($prestado->fetchColumn() > 0) {
                $error = 'Ese libro ya está prestado.';
            } elseif ($actuales['retrasados'] > 0) {
                $error = 'Este lector tiene libros con retraso: debe devolverlos antes de llevarse otro.';
            } elseif ($actuales['total'] >= MAX_PRESTAMOS) {
                $error = 'Este lector ya tiene ' . MAX_PRESTAMOS . ' libros prestados, que es el máximo.';
            }

            if ($error) {
                $pdo->rollBack();
            } else {
                $insertar = $pdo->prepare(
                    'INSERT INTO prestamos (libro_id, lector_id, fecha_prestamo) VALUES (?, ?, CURDATE())'
                );
                $insertar->execute([$libroId, $lectorId]);
                $pdo->commit();

                // Redirige para que F5 no repita el préstamo
                flash('aviso', 'Préstamo registrado. Debe devolverse antes del '
                    . date('d/m/Y', strtotime('+' . PLAZO_DIAS . ' days')) . '.');
                redirigir('index.php');
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'No se ha podido registrar el préstamo.';
        }
    }
}

// Lectores, con cuántos libros tienen ahora mismo
$lectores = $pdo->query(
    'SELECT le.id, le.nombre, le.apellidos,
            COUNT(p.id) AS prestados
     FROM lectores le
     LEFT JOIN prestamos p ON p.lector_id = le.id AND p.fecha_devolucion IS NULL
     GROUP BY le.id, le.nombre, le.apellidos
     ORDER BY le.apellidos, le.nombre'
)->fetchAll();

// Solo los libros activos que no tienen un préstamo sin devolver
$libros = $pdo->query(
    'SELECT id, titulo, autor FROM libros
     WHERE activo = 1
       AND id NOT IN (SELECT libro_id FROM prestamos WHERE fecha_devolucion IS NULL)
     ORDER BY titulo'
)->fetchAll();

$titulo = 'Nuevo préstamo';
require 'cabecera.php';
?>

<?php if ($error): ?>
  <p class="error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<?php if (!$libros): ?>
  <p class="vacio">Todos los libros están prestados.</p>
<?php else: ?>
  <form method="post" class="tarjeta formulario">
    <?= campo_csrf() ?>

    <label for="lector_id">Lector</label>
    <select name="lector_id" id="lector_id" required>
      <option value="">Elige un lector…</option>
      <?php foreach ($lectores as $le): ?>
        <option value="<?= $le['id'] ?>" <?= $le['id'] == $lectorId ? 'selected' : '' ?>>
          <?= e($le['apellidos'] . ', ' . $le['nombre']) ?>
          (<?= $le['prestados'] ?>/<?= MAX_PRESTAMOS ?>)
        </option>
      <?php endforeach; ?>
    </select>
    <p class="ayuda">Entre paréntesis, los libros que ya tiene cada lector.
      ¿No está en la lista? <a href="registro.php">Registra un lector nuevo</a>.</p>

    <label for="libro_id">Libro disponible</label>
    <select name="libro_id" id="libro_id" required>
      <option value="">Elige un libro…</option>
      <?php foreach ($libros as $li): ?>
        <option value="<?= $li['id'] ?>" <?= $li['id'] == $libroId ? 'selected' : '' ?>>
          <?= e($li['titulo'] . ' (' . $li['autor'] . ')') ?>
        </option>
      <?php endforeach; ?>
    </select>

    <p class="ayuda">El préstamo dura <?= PLAZO_DIAS ?> días: vencerá el
      <strong><?= date('d/m/Y', strtotime('+' . PLAZO_DIAS . ' days')) ?></strong>.</p>

    <button type="submit" class="boton">Prestar</button>
  </form>
<?php endif; ?>

<?php require 'pie.php'; ?>
