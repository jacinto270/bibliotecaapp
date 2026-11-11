<?php
require 'conexion.php';

// Cifras generales de todos los préstamos
$general = $pdo->prepare(
    'SELECT COUNT(*) AS total,
            ROUND(AVG(CASE WHEN fecha_devolucion IS NOT NULL
                           THEN DATEDIFF(fecha_devolucion, fecha_prestamo) END), 1) AS media_dias,
            SUM(fecha_devolucion IS NOT NULL) AS devueltos,
            SUM(fecha_devolucion IS NOT NULL
                AND DATEDIFF(fecha_devolucion, fecha_prestamo) <= ?) AS a_tiempo
     FROM prestamos'
);
$general->execute([PLAZO_DIAS]);
$cifras = $general->fetch();
$puntualidad = $cifras['devueltos'] ? round(100 * $cifras['a_tiempo'] / $cifras['devueltos']) : null;

// Los 5 libros más prestados
$topLibros = $pdo->query(
    'SELECT l.titulo AS nombre, l.autor AS detalle, COUNT(*) AS valor
     FROM prestamos p JOIN libros l ON l.id = p.libro_id
     GROUP BY l.id, l.titulo, l.autor
     ORDER BY valor DESC, l.titulo
     LIMIT 5'
)->fetchAll();

// Los 5 lectores que más leen
$topLectores = $pdo->query(
    "SELECT CONCAT(le.nombre, ' ', le.apellidos) AS nombre, le.id, COUNT(*) AS valor
     FROM prestamos p JOIN lectores le ON le.id = p.lector_id
     GROUP BY le.id, le.nombre, le.apellidos
     ORDER BY valor DESC, nombre
     LIMIT 5"
)->fetchAll();

// Préstamos por género
$porGenero = $pdo->query(
    'SELECT l.genero AS nombre, COUNT(*) AS valor
     FROM prestamos p JOIN libros l ON l.id = p.libro_id
     GROUP BY l.genero
     ORDER BY valor DESC, l.genero'
)->fetchAll();

// Préstamos de los últimos 6 meses
$porMes = $pdo->query(
    "SELECT DATE_FORMAT(fecha_prestamo, '%Y-%m') AS mes, COUNT(*) AS valor
     FROM prestamos
     WHERE fecha_prestamo >= DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH, '%Y-%m-01')
     GROUP BY mes
     ORDER BY mes"
)->fetchAll();

// Libros activos que nunca se han prestado: joyas por descubrir
$sinPrestar = $pdo->query(
    'SELECT titulo, autor, genero FROM libros
     WHERE activo = 1 AND id NOT IN (SELECT libro_id FROM prestamos)
     ORDER BY titulo'
)->fetchAll();

$meses = ['01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril', '05' => 'mayo', '06' => 'junio',
          '07' => 'julio', '08' => 'agosto', '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'];

// Dibuja una lista ordenada con una barra proporcional a cada valor.
// La barra es SVG (el ancho va en un atributo, así cumple la política CSP)
// y el valor siempre se escribe en texto, no depende solo del dibujo.
function ranking(array $filas, string $unidad): string
{
    if (!$filas) {
        return '<p class="vacio">Todavía no hay datos.</p>';
    }
    $max = max(array_column($filas, 'valor'));
    $html = '<ol class="ranking">';
    foreach ($filas as $f) {
        $ancho = round(100 * $f['valor'] / $max, 1);
        $html .= '<li>'
            . '<span class="ranking__nombre">' . ($f['enlace'] ?? e($f['nombre'])) . '</span>'
            . '<span class="ranking__valor">' . $f['valor'] . ' ' . $unidad . '</span>'
            . '<svg class="ranking__barra" viewBox="0 0 100 8" preserveAspectRatio="none" aria-hidden="true">'
            . '<title>' . e($f['nombre']) . ': ' . $f['valor'] . ' ' . $unidad . '</title>'
            . '<rect width="100" height="8" rx="4" class="ranking__fondo"/>'
            . '<rect width="' . $ancho . '" height="8" rx="4" class="ranking__relleno"/>'
            . '</svg></li>';
    }
    return $html . '</ol>';
}

// Los lectores llevan enlace a su ficha
foreach ($topLectores as &$fila) {
    $fila['enlace'] = '<a href="lector.php?id=' . $fila['id'] . '">' . e($fila['nombre']) . '</a>';
}
unset($fila);

foreach ($porGenero as &$fila) {
    $fila['nombre'] = ucfirst($fila['nombre']);
}
unset($fila);

foreach ($porMes as &$fila) {
    [$anio, $mes] = explode('-', $fila['mes']);
    $fila['nombre'] = ucfirst($meses[$mes]) . ' ' . $anio;
}
unset($fila);

$titulo = 'Estadísticas';
require 'cabecera.php';
?>

<section class="cifras" aria-label="Resumen">
  <div class="cifra">
    <span class="cifra__valor"><?= $cifras['total'] ?></span>
    <span class="cifra__texto">préstamos en total</span>
  </div>
  <div class="cifra">
    <span class="cifra__valor"><?= $cifras['media_dias'] ?? '—' ?></span>
    <span class="cifra__texto">días de media por préstamo</span>
  </div>
  <div class="cifra">
    <span class="cifra__valor"><?= $puntualidad !== null ? $puntualidad . ' %' : '—' ?></span>
    <span class="cifra__texto">devueltos a tiempo</span>
  </div>
  <div class="cifra">
    <span class="cifra__valor"><?= count($sinPrestar) ?></span>
    <span class="cifra__texto">libros sin estrenar</span>
  </div>
</section>

<div class="paneles">
  <section class="tarjeta">
    <h2>Libros más prestados</h2>
    <?= ranking($topLibros, 'préstamos') ?>
  </section>

  <section class="tarjeta">
    <h2>Lectores más activos</h2>
    <?= ranking($topLectores, 'libros') ?>
  </section>

  <section class="tarjeta">
    <h2>Préstamos por género</h2>
    <?= ranking($porGenero, 'préstamos') ?>
  </section>

  <section class="tarjeta">
    <h2>Préstamos de los últimos meses</h2>
    <?= ranking($porMes, 'préstamos') ?>
  </section>
</div>

<section class="tarjeta">
  <h2>Joyas por descubrir</h2>
  <p class="ayuda">Libros del catálogo que todavía nadie se ha llevado. ¡Buenos candidatos para recomendar!</p>
  <?php if (!$sinPrestar): ?>
    <p class="vacio">Todos los libros se han prestado alguna vez.</p>
  <?php else: ?>
    <ul class="joyas">
      <?php foreach ($sinPrestar as $l): ?>
        <li>
          <strong><?= e($l['titulo']) ?></strong>
          <span class="secundario"><?= e($l['autor']) ?></span>
          <span class="etiqueta"><?= e($l['genero']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<?php require 'pie.php'; ?>
