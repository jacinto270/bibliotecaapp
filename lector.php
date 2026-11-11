<?php
require 'conexion.php';

// Lector elegido (llega por GET: lector.php?id=3)
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    redirigir('lectores.php');
}

$consulta = $pdo->prepare(
    'SELECT nombre, apellidos, dni, telefono, email, fecha_alta FROM lectores WHERE id = ?'
);
$consulta->execute([$id]);
$lector = $consulta->fetch();

if (!$lector) {
    flash('error', 'Ese lector no existe.');
    redirigir('lectores.php');
}

// Todos sus préstamos, del más reciente al más antiguo, con los días que
// tuvo cada libro (o que lleva, si aún no lo ha devuelto)
$consulta = $pdo->prepare(
    'SELECT p.id, l.titulo, l.autor, l.genero, p.fecha_prestamo, p.fecha_devolucion,
            DATEDIFF(COALESCE(p.fecha_devolucion, CURDATE()), p.fecha_prestamo) AS dias
     FROM prestamos p
     JOIN libros l ON l.id = p.libro_id
     WHERE p.lector_id = ?
     ORDER BY p.fecha_prestamo DESC, p.id DESC'
);
$consulta->execute([$id]);
$historial = $consulta->fetchAll();

// Separa los que tiene ahora de los ya devueltos y calcula sus cifras
$enCurso = array_filter($historial, fn($h) => $h['fecha_devolucion'] === null);
$devueltos = array_filter($historial, fn($h) => $h['fecha_devolucion'] !== null);

$mediaDias = $devueltos
    ? round(array_sum(array_column($devueltos, 'dias')) / count($devueltos), 1)
    : null;

$generos = array_count_values(array_column($historial, 'genero'));
arsort($generos);
$generoFavorito = $generos ? array_key_first($generos) : null;

$titulo = $lector['nombre'] . ' ' . $lector['apellidos'];
require 'cabecera.php';
?>

<section class="tarjeta ficha">
  <dl>
    <div><dt>DNI</dt><dd><?= $lector['dni'] ? e($lector['dni']) : '—' ?></dd></div>
    <div><dt>Teléfono</dt><dd><?= e($lector['telefono']) ?></dd></div>
    <div><dt>Correo</dt><dd><?= $lector['email'] ? e($lector['email']) : '—' ?></dd></div>
    <div><dt>Socio desde</dt><dd><?= fecha($lector['fecha_alta']) ?></dd></div>
  </dl>
  <?php if (count($enCurso) < MAX_PRESTAMOS): ?>
    <a class="boton" href="nuevo.php?lector=<?= $id ?>">+ Prestarle un libro</a>
  <?php endif; ?>
</section>

<section class="cifras" aria-label="Resumen del lector">
  <div class="cifra">
    <span class="cifra__valor"><?= count($historial) ?></span>
    <span class="cifra__texto">libros leídos</span>
  </div>
  <div class="cifra">
    <span class="cifra__valor"><?= count($enCurso) ?>/<?= MAX_PRESTAMOS ?></span>
    <span class="cifra__texto">libros en casa ahora</span>
  </div>
  <div class="cifra">
    <span class="cifra__valor"><?= $mediaDias ?? '—' ?></span>
    <span class="cifra__texto">días de media por libro</span>
  </div>
  <div class="cifra">
    <span class="cifra__valor cifra__valor--texto"><?= $generoFavorito ? e(ucfirst($generoFavorito)) : '—' ?></span>
    <span class="cifra__texto">género favorito</span>
  </div>
</section>

<h2>Libros que tiene ahora</h2>
<?php if (!$enCurso): ?>
  <p class="vacio">No tiene ningún libro en casa.</p>
<?php else: ?>
  <div class="tabla">
    <table>
      <thead>
        <tr>
          <th>Libro</th>
          <th>Prestado el</th>
          <th class="num">Días con el libro</th>
          <th>Estado</th>
          <th><span class="oculto">Acción</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($enCurso as $h): ?>
          <?php $estado = estado_prestamo($h['dias']); ?>
          <tr>
            <td><strong><?= e($h['titulo']) ?></strong> <span class="secundario"><?= e($h['autor']) ?></span></td>
            <td><?= fecha($h['fecha_prestamo']) ?></td>
            <td class="num"><?= $h['dias'] ?></td>
            <td>
              <span class="estado estado--<?= $estado['clase'] ?>">
                <span aria-hidden="true"><?= $estado['icono'] ?></span> <?= $estado['texto'] ?>
              </span>
            </td>
            <td>
              <form action="devolver.php" method="post"
                    data-confirmar="¿Registrar la devolución de «<?= e($h['titulo']) ?>»?">
                <?= campo_csrf() ?>
                <input type="hidden" name="id" value="<?= $h['id'] ?>">
                <button type="submit" class="boton boton--secundario">Devolver</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<h2>Historial de lecturas</h2>
<?php if (!$devueltos): ?>
  <p class="vacio">Todavía no ha devuelto ningún libro.</p>
<?php else: ?>
  <div class="tabla">
    <table>
      <thead>
        <tr>
          <th>Libro</th>
          <th>Género</th>
          <th>Prestado el</th>
          <th>Devuelto el</th>
          <th class="num">Días</th>
          <th>Devolución</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($devueltos as $h): ?>
          <tr>
            <td><strong><?= e($h['titulo']) ?></strong> <span class="secundario"><?= e($h['autor']) ?></span></td>
            <td><span class="etiqueta"><?= e($h['genero']) ?></span></td>
            <td><?= fecha($h['fecha_prestamo']) ?></td>
            <td><?= fecha($h['fecha_devolucion']) ?></td>
            <td class="num"><?= $h['dias'] ?></td>
            <td>
              <?php if ($h['dias'] > PLAZO_DIAS): ?>
                <span class="estado estado--retrasado"><span aria-hidden="true">!</span> Con <?= $h['dias'] - PLAZO_DIAS ?> d de retraso</span>
              <?php else: ?>
                <span class="estado estado--en-plazo"><span aria-hidden="true">✓</span> A tiempo</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require 'pie.php'; ?>
