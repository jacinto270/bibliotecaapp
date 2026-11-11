<?php
require 'conexion.php';

// Filtros opcionales: libros.php?q=zafón&genero=novela&ver=bajas
$q = mb_substr(trim((string) filter_input(INPUT_GET, 'q')), 0, 60, 'UTF-8');
$genero = (string) filter_input(INPUT_GET, 'genero');
$genero = in_array($genero, GENEROS, true) ? $genero : '';
$verBajas = ($_GET['ver'] ?? '') === 'bajas';

// Cada libro con las veces que se ha prestado y, si está prestado ahora,
// a quién y cuántos días lleva fuera
$sql = 'SELECT l.id, l.isbn, l.titulo, l.autor, l.anio, l.genero,
               (SELECT COUNT(*) FROM prestamos WHERE libro_id = l.id) AS veces,
               p.lector_id, DATEDIFF(CURDATE(), p.fecha_prestamo) AS dias,
               le.nombre, le.apellidos
        FROM libros l
        LEFT JOIN prestamos p ON p.libro_id = l.id AND p.fecha_devolucion IS NULL
        LEFT JOIN lectores le ON le.id = p.lector_id
        WHERE l.activo = ?';
$parametros = [$verBajas ? 0 : 1];

if ($q !== '') {
    $patron = '%' . addcslashes($q, '%_\\') . '%';
    $sql .= ' AND (l.titulo LIKE ? OR l.autor LIKE ? OR l.isbn LIKE ?)';
    array_push($parametros, $patron, $patron, $patron);
}
if ($genero !== '') {
    $sql .= ' AND l.genero = ?';
    $parametros[] = $genero;
}
$sql .= ' ORDER BY l.titulo';

$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);
$libros = $consulta->fetchAll();

$titulo = 'Catálogo de libros';
require 'cabecera.php';
?>

<div class="barra">
  <form method="get" class="buscador" role="search">
    <?php if ($verBajas): ?><input type="hidden" name="ver" value="bajas"><?php endif; ?>
    <label for="q" class="oculto">Buscar libro</label>
    <input type="search" name="q" id="q" value="<?= e($q) ?>" placeholder="Título, autor o ISBN…">
    <label for="genero" class="oculto">Género</label>
    <select name="genero" id="genero">
      <option value="">Todos los géneros</option>
      <?php foreach (GENEROS as $g): ?>
        <option value="<?= e($g) ?>" <?= $g === $genero ? 'selected' : '' ?>><?= e(ucfirst($g)) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="boton boton--secundario">Filtrar</button>
  </form>
  <a class="boton" href="libro_nuevo.php">+ Añadir libro</a>
</div>

<div class="pestanas">
  <a href="libros.php" <?= !$verBajas ? 'aria-current="page"' : '' ?>>En catálogo</a>
  <a href="libros.php?ver=bajas" <?= $verBajas ? 'aria-current="page"' : '' ?>>Dados de baja</a>
</div>

<?php if (!$libros): ?>
  <p class="vacio">No hay ningún libro con esos criterios.</p>
<?php else: ?>
  <div class="tabla">
    <table>
      <thead>
        <tr>
          <th>Libro</th>
          <th>Género</th>
          <th class="num">Año</th>
          <th class="num">Préstamos</th>
          <th>Situación</th>
          <th><span class="oculto">Acción</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($libros as $l): ?>
          <tr>
            <td>
              <strong><?= e($l['titulo']) ?></strong>
              <span class="secundario"><?= e($l['autor']) ?> · ISBN <?= e($l['isbn']) ?></span>
            </td>
            <td><span class="etiqueta"><?= e($l['genero']) ?></span></td>
            <td class="num"><?= $l['anio'] ?? '—' ?></td>
            <td class="num"><?= $l['veces'] ?></td>
            <td>
              <?php if ($verBajas): ?>
                <span class="secundario">De baja</span>
              <?php elseif ($l['lector_id']): ?>
                <?php $estado = estado_prestamo($l['dias']); ?>
                <span class="estado estado--<?= $estado['clase'] ?>">
                  <span aria-hidden="true"><?= $estado['icono'] ?></span> Prestado · <?= $l['dias'] ?> días
                </span>
                <span class="secundario">a <a href="lector.php?id=<?= $l['lector_id'] ?>"><?= e($l['nombre'] . ' ' . $l['apellidos']) ?></a></span>
              <?php else: ?>
                <span class="estado estado--disponible"><span aria-hidden="true">●</span> Disponible</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($verBajas): ?>
                <form action="libro_quitar.php" method="post">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="id" value="<?= $l['id'] ?>">
                  <input type="hidden" name="accion" value="recuperar">
                  <button type="submit" class="boton boton--secundario">Recuperar</button>
                </form>
              <?php elseif (!$l['lector_id']): ?>
                <form action="libro_quitar.php" method="post"
                      data-confirmar="¿Quitar «<?= e($l['titulo']) ?>» del catálogo?">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="id" value="<?= $l['id'] ?>">
                  <input type="hidden" name="accion" value="quitar">
                  <button type="submit" class="boton boton--peligro">Quitar</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require 'pie.php'; ?>
