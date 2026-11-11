<?php
require 'conexion.php';

// Filtro opcional: index.php?ver=retrasados
$soloRetrasados = ($_GET['ver'] ?? '') === 'retrasados';

// Préstamos sin devolver, del más antiguo al más reciente, con los días que
// lleva cada libro fuera y la fecha en que vence el plazo
$sql = 'SELECT p.id, p.lector_id, p.fecha_prestamo,
               DATEDIFF(CURDATE(), p.fecha_prestamo) AS dias,
               DATE_ADD(p.fecha_prestamo, INTERVAL ? DAY) AS vence,
               l.titulo, l.autor, le.nombre, le.apellidos
        FROM prestamos p
        JOIN libros l ON l.id = p.libro_id
        JOIN lectores le ON le.id = p.lector_id
        WHERE p.fecha_devolucion IS NULL';
if ($soloRetrasados) {
    $sql .= ' AND DATEDIFF(CURDATE(), p.fecha_prestamo) > ?';
}
$sql .= ' ORDER BY p.fecha_prestamo, p.id';

$consulta = $pdo->prepare($sql);
$consulta->execute($soloRetrasados ? [PLAZO_DIAS, PLAZO_DIAS] : [PLAZO_DIAS]);
$prestamos = $consulta->fetchAll();

// Cifras del resumen
$resumen = $pdo->prepare(
    'SELECT
       (SELECT COUNT(*) FROM libros WHERE activo = 1) AS libros,
       (SELECT COUNT(*) FROM prestamos WHERE fecha_devolucion IS NULL) AS prestados,
       (SELECT COUNT(*) FROM lectores) AS lectores,
       (SELECT COUNT(*) FROM prestamos
         WHERE fecha_devolucion IS NULL AND DATEDIFF(CURDATE(), fecha_prestamo) > ?) AS retrasados'
);
$resumen->execute([PLAZO_DIAS]);
$cifras = $resumen->fetch();

$titulo = 'Préstamos en curso';
require 'cabecera.php';
?>

<section class="cifras" aria-label="Resumen">
  <div class="cifra">
    <span class="cifra__valor"><?= $cifras['libros'] ?></span>
    <span class="cifra__texto">libros en el catálogo</span>
  </div>
  <div class="cifra">
    <span class="cifra__valor"><?= $cifras['prestados'] ?></span>
    <span class="cifra__texto">prestados ahora</span>
  </div>
  <div class="cifra">
    <span class="cifra__valor"><?= $cifras['lectores'] ?></span>
    <span class="cifra__texto">lectores registrados</span>
  </div>
  <a class="cifra <?= $cifras['retrasados'] ? 'cifra--alerta' : '' ?>" href="index.php?ver=retrasados">
    <span class="cifra__valor"><?= $cifras['retrasados'] ?></span>
    <span class="cifra__texto">con retraso (más de <?= PLAZO_DIAS ?> días)</span>
  </a>
</section>

<div class="barra">
  <div class="pestanas">
    <a href="index.php" <?= !$soloRetrasados ? 'aria-current="page"' : '' ?>>Todos</a>
    <a href="index.php?ver=retrasados" <?= $soloRetrasados ? 'aria-current="page"' : '' ?>>Solo retrasados</a>
  </div>
  <a class="boton" href="nuevo.php">+ Nuevo préstamo</a>
</div>

<?php if (!$prestamos): ?>
  <p class="vacio"><?= $soloRetrasados ? 'No hay ningún préstamo retrasado.' : 'No hay ningún libro prestado.' ?></p>
<?php else: ?>
  <div class="tabla">
    <table>
      <thead>
        <tr>
          <th>Libro</th>
          <th>Lector</th>
          <th>Prestado el</th>
          <th class="num">Días</th>
          <th>Vence el</th>
          <th>Estado</th>
          <th><span class="oculto">Acción</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($prestamos as $p): ?>
          <?php $estado = estado_prestamo($p['dias']); ?>
          <tr>
            <td>
              <strong><?= e($p['titulo']) ?></strong>
              <span class="secundario"><?= e($p['autor']) ?></span>
            </td>
            <td>
              <a href="lector.php?id=<?= $p['lector_id'] ?>"><?= e($p['nombre'] . ' ' . $p['apellidos']) ?></a>
            </td>
            <td><?= fecha($p['fecha_prestamo']) ?></td>
            <td class="num"><?= $p['dias'] ?></td>
            <td><?= fecha($p['vence']) ?></td>
            <td>
              <span class="estado estado--<?= $estado['clase'] ?>">
                <span aria-hidden="true"><?= $estado['icono'] ?></span> <?= $estado['texto'] ?>
              </span>
            </td>
            <td>
              <form action="devolver.php" method="post"
                    data-confirmar="¿Registrar la devolución de «<?= e($p['titulo']) ?>»?">
                <?= campo_csrf() ?>
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="boton boton--secundario">Devolver</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require 'pie.php'; ?>
