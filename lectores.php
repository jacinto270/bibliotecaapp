<?php
require 'conexion.php';

// Búsqueda opcional por nombre, apellidos, DNI o correo: lectores.php?q=rosa
$q = trim((string) filter_input(INPUT_GET, 'q'));
$q = mb_substr($q, 0, 60, 'UTF-8');

// Cada lector con los libros que tiene ahora, los que ha leído en total
// y los días que lleva su préstamo más antiguo
$sql = 'SELECT le.id, le.nombre, le.apellidos, le.dni, le.telefono, le.email, le.fecha_alta,
               SUM(p.id IS NOT NULL AND p.fecha_devolucion IS NULL) AS prestados,
               COUNT(p.id) AS total,
               MAX(CASE WHEN p.fecha_devolucion IS NULL
                        THEN DATEDIFF(CURDATE(), p.fecha_prestamo) END) AS dias_max
        FROM lectores le
        LEFT JOIN prestamos p ON p.lector_id = le.id';
$parametros = [];
if ($q !== '') {
    // Los comodines % y _ que escriba el usuario se buscan como texto normal
    $patron = '%' . addcslashes($q, '%_\\') . '%';
    $sql .= " WHERE CONCAT(le.nombre, ' ', le.apellidos) LIKE ? OR le.dni LIKE ? OR le.email LIKE ?";
    $parametros = [$patron, $patron, $patron];
}
$sql .= ' GROUP BY le.id, le.nombre, le.apellidos, le.dni, le.telefono, le.email, le.fecha_alta
          ORDER BY le.apellidos, le.nombre';

$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);
$lectores = $consulta->fetchAll();

$titulo = 'Lectores';
require 'cabecera.php';
?>

<div class="barra">
  <form method="get" class="buscador" role="search">
    <label for="q" class="oculto">Buscar lector</label>
    <input type="search" name="q" id="q" value="<?= e($q) ?>" placeholder="Buscar por nombre, DNI o correo…">
    <button type="submit" class="boton boton--secundario">Buscar</button>
  </form>
  <a class="boton" href="registro.php">+ Registrar lector</a>
</div>

<?php if (!$lectores): ?>
  <p class="vacio">No hay ningún lector<?= $q !== '' ? ' que coincida con «' . e($q) . '»' : '' ?>.</p>
<?php else: ?>
  <div class="tabla">
    <table>
      <thead>
        <tr>
          <th>Lector</th>
          <th>Contacto</th>
          <th>Socio desde</th>
          <th class="num">Leídos</th>
          <th>Ahora tiene</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($lectores as $le): ?>
          <tr>
            <td>
              <a href="lector.php?id=<?= $le['id'] ?>"><strong><?= e($le['nombre'] . ' ' . $le['apellidos']) ?></strong></a>
              <span class="secundario"><?= $le['dni'] ? e($le['dni']) : 'Sin DNI' ?></span>
            </td>
            <td>
              <?= e($le['telefono']) ?>
              <span class="secundario"><?= $le['email'] ? e($le['email']) : 'Sin correo' ?></span>
            </td>
            <td><?= fecha($le['fecha_alta']) ?></td>
            <td class="num"><?= $le['total'] ?></td>
            <td>
              <?php if ($le['prestados'] > 0): ?>
                <?php $estado = estado_prestamo($le['dias_max']); ?>
                <span class="estado estado--<?= $estado['clase'] ?>">
                  <span aria-hidden="true"><?= $estado['icono'] ?></span>
                  <?= $le['prestados'] ?> <?= $le['prestados'] == 1 ? 'libro' : 'libros' ?> · <?= $le['dias_max'] ?> días
                </span>
              <?php else: ?>
                <span class="secundario">Ninguno</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require 'pie.php'; ?>
