<?php
// Gestión de cuentas: solo administradores
require 'conexion.php';
requerir_admin($usuarioActual);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprobar_csrf();

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $accion = $_POST['accion'] ?? '';

    if (!$id || $id === (int) $usuarioActual['id']) {
        flash('error', 'No puedes cambiar tu propia cuenta desde aquí.');
    } else {
        $cambios = [
            'activar' => ['UPDATE usuarios SET activo = 1 WHERE id = ?', 'Cuenta activada.'],
            'desactivar' => ['UPDATE usuarios SET activo = 0 WHERE id = ?', 'Cuenta desactivada: ya no puede entrar.'],
            'hacer_admin' => ["UPDATE usuarios SET rol = 'admin' WHERE id = ?", 'Ahora es administrador.'],
            'quitar_admin' => ["UPDATE usuarios SET rol = 'bibliotecario' WHERE id = ?", 'Ya no es administrador.'],
            'borrar' => ['DELETE FROM usuarios WHERE id = ? AND activo = 0', 'Solicitud borrada.'],
        ];
        if (isset($cambios[$accion])) {
            $pdo->prepare($cambios[$accion][0])->execute([$id]);
            flash('aviso', $cambios[$accion][1]);
        }
    }
    redirigir('usuarios.php');
}

$usuarios = $pdo->query(
    'SELECT id, usuario, nombre, rol, activo, creado, ultimo_acceso
     FROM usuarios ORDER BY activo, creado DESC'
)->fetchAll();

$titulo = 'Usuarios';
require 'cabecera.php';

// Botón de acción con su formulario POST
function boton_usuario(int $id, string $accion, string $texto, string $clase = 'boton--secundario', string $confirmar = ''): string
{
    return '<form method="post"' . ($confirmar ? ' data-confirmar="' . e($confirmar) . '"' : '') . '>'
        . campo_csrf()
        . '<input type="hidden" name="id" value="' . $id . '">'
        . '<input type="hidden" name="accion" value="' . $accion . '">'
        . '<button type="submit" class="boton ' . $clase . '">' . e($texto) . '</button></form>';
}
?>

<p class="entradilla">Las cuentas nuevas quedan pendientes hasta que las actives. Desactiva las que no reconozcas.</p>

<div class="tabla">
  <table>
    <thead>
      <tr>
        <th>Usuario</th>
        <th>Rol</th>
        <th>Estado</th>
        <th>Último acceso</th>
        <th><span class="oculto">Acciones</span></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($usuarios as $u): ?>
        <?php $yo = (int) $u['id'] === (int) $usuarioActual['id']; ?>
        <tr>
          <td>
            <strong><?= e($u['nombre']) ?></strong>
            <span class="secundario">@<?= e($u['usuario']) ?> · desde <?= fecha(substr($u['creado'], 0, 10)) ?></span>
          </td>
          <td><span class="etiqueta"><?= $u['rol'] === 'admin' ? 'Administrador' : 'Bibliotecario' ?></span></td>
          <td>
            <?php if ($u['activo']): ?>
              <span class="estado estado--disponible"><span aria-hidden="true">●</span> Activa</span>
            <?php else: ?>
              <span class="estado estado--pronto"><span aria-hidden="true">◷</span> Pendiente / desactivada</span>
            <?php endif; ?>
          </td>
          <td><?= $u['ultimo_acceso'] ? e(date('d/m/Y H:i', strtotime($u['ultimo_acceso']))) : '<span class="secundario">Nunca</span>' ?></td>
          <td>
            <?php if ($yo): ?>
              <span class="secundario">Tu cuenta</span>
            <?php else: ?>
              <div class="acciones">
                <?php if ($u['activo']): ?>
                  <?= boton_usuario($u['id'], 'desactivar', 'Desactivar', 'boton--peligro') ?>
                  <?= $u['rol'] === 'admin'
                      ? boton_usuario($u['id'], 'quitar_admin', 'Quitar admin')
                      : boton_usuario($u['id'], 'hacer_admin', 'Hacer admin', 'boton--secundario', '¿Dar permisos de administrador a ' . $u['usuario'] . '?') ?>
                <?php else: ?>
                  <?= boton_usuario($u['id'], 'activar', 'Activar', '') ?>
                  <?= boton_usuario($u['id'], 'borrar', 'Borrar', 'boton--peligro', '¿Borrar la cuenta de ' . $u['usuario'] . '?') ?>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require 'pie.php'; ?>
