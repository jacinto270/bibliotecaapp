<?php
require 'conexion.php';

$errores = [];
$datos = ['nombre' => '', 'apellidos' => '', 'dni' => '', 'telefono' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprobar_csrf();

    // Trampa para robots: un campo oculto que una persona nunca rellena,
    // y un tiempo mínimo para rellenar el formulario
    $esRobot = texto_post('web') !== ''
        || time() - ($_SESSION['registro_inicio'] ?? 0) < 3;

    $datos['nombre'] = texto_post('nombre');
    $datos['apellidos'] = texto_post('apellidos');
    $datos['dni'] = strtoupper(str_replace(['-', ' '], '', texto_post('dni')));
    $datos['telefono'] = texto_post('telefono');
    $datos['email'] = mb_strtolower(texto_post('email'), 'UTF-8');

    // Validación en el servidor (la del navegador se puede saltar)
    if ($esRobot) {
        $errores['general'] = 'No se ha podido completar el registro. Inténtalo de nuevo.';
    }

    if ($datos['nombre'] === '') {
        $errores['nombre'] = 'Escribe el nombre.';
    } elseif (longitud($datos['nombre']) > 40 || !nombre_valido($datos['nombre'])) {
        $errores['nombre'] = 'El nombre solo puede tener letras, espacios y guiones (máximo 40).';
    }

    if ($datos['apellidos'] === '') {
        $errores['apellidos'] = 'Escribe los apellidos.';
    } elseif (longitud($datos['apellidos']) > 60 || !nombre_valido($datos['apellidos'])) {
        $errores['apellidos'] = 'Los apellidos solo pueden tener letras, espacios y guiones (máximo 60).';
    }

    if (!dni_valido($datos['dni'])) {
        $errores['dni'] = 'El DNI o NIE no es válido: revisa los números y la letra.';
    }

    $telefono = telefono_normalizado($datos['telefono']);
    if ($telefono === null) {
        $errores['telefono'] = 'Escribe un teléfono español de 9 cifras.';
    }

    if (longitud($datos['email']) > 100 || !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'Escribe un correo electrónico válido.';
    }

    if (($_POST['privacidad'] ?? '') !== 'si') {
        $errores['privacidad'] = 'Debes aceptar el tratamiento de tus datos para registrarte.';
    }

    // ¿DNI o correo ya registrados?
    if (!$errores) {
        $repetido = $pdo->prepare('SELECT dni, email FROM lectores WHERE dni = ? OR email = ?');
        $repetido->execute([$datos['dni'], $datos['email']]);
        foreach ($repetido->fetchAll() as $fila) {
            if ($fila['dni'] === $datos['dni']) {
                $errores['dni'] = 'Ya hay un lector registrado con ese DNI.';
            }
            if ($fila['email'] === $datos['email']) {
                $errores['email'] = 'Ya hay un lector registrado con ese correo.';
            }
        }
    }

    if (!$errores) {
        try {
            $alta = $pdo->prepare(
                'INSERT INTO lectores (nombre, apellidos, dni, telefono, email, fecha_alta)
                 VALUES (?, ?, ?, ?, ?, CURDATE())'
            );
            $alta->execute([$datos['nombre'], $datos['apellidos'], $datos['dni'], $telefono, $datos['email']]);
            $nuevoId = $pdo->lastInsertId();

            unset($_SESSION['registro_inicio']);
            flash('aviso', 'Bienvenido/a, ' . $datos['nombre'] . '. El registro se ha completado.');
            redirigir('lector.php?id=' . $nuevoId);
        } catch (PDOException $e) {
            // 23000: clave única repetida (alguien se registró a la vez con el mismo dato)
            $errores['general'] = $e->getCode() === '23000'
                ? 'Ese DNI o correo ya está registrado.'
                : 'No se ha podido completar el registro.';
        }
    }
}

// Momento en que se muestra el formulario (para la trampa anti-robots)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['registro_inicio'])) {
    $_SESSION['registro_inicio'] = time();
}

$titulo = 'Registro de lectores';
require 'cabecera.php';
?>

<p class="entradilla">Hazte socio/a de la biblioteca: con tu carné podrás llevarte
  hasta <?= MAX_PRESTAMOS ?> libros a la vez durante <?= PLAZO_DIAS ?> días.</p>

<?php if ($errores): ?>
  <div class="error" role="alert">
    <?= isset($errores['general']) ? e($errores['general']) : 'Revisa los campos marcados en rojo.' ?>
  </div>
<?php endif; ?>

<form method="post" class="tarjeta formulario" novalidate>
  <?= campo_csrf() ?>

  <!-- Trampa para robots: oculto para las personas -->
  <div class="trampa" aria-hidden="true">
    <label for="web">No rellenes este campo</label>
    <input type="text" name="web" id="web" tabindex="-1" autocomplete="off">
  </div>

  <div class="rejilla">
    <div>
      <label for="nombre">Nombre</label>
      <input type="text" name="nombre" id="nombre" value="<?= e($datos['nombre']) ?>"
             maxlength="40" autocomplete="given-name" required <?= marca_error($errores, 'nombre') ?>>
      <?= error_campo($errores, 'nombre') ?>
    </div>

    <div>
      <label for="apellidos">Apellidos</label>
      <input type="text" name="apellidos" id="apellidos" value="<?= e($datos['apellidos']) ?>"
             maxlength="60" autocomplete="family-name" required <?= marca_error($errores, 'apellidos') ?>>
      <?= error_campo($errores, 'apellidos') ?>
    </div>

    <div>
      <label for="dni">DNI o NIE</label>
      <input type="text" name="dni" id="dni" value="<?= e($datos['dni']) ?>"
             maxlength="10" placeholder="12345678Z" required <?= marca_error($errores, 'dni') ?>>
      <?= error_campo($errores, 'dni') ?>
    </div>

    <div>
      <label for="telefono">Teléfono</label>
      <input type="tel" name="telefono" id="telefono" value="<?= e($datos['telefono']) ?>"
             maxlength="16" autocomplete="tel" placeholder="600 123 456" required <?= marca_error($errores, 'telefono') ?>>
      <?= error_campo($errores, 'telefono') ?>
    </div>

    <div class="rejilla__ancho">
      <label for="email">Correo electrónico</label>
      <input type="email" name="email" id="email" value="<?= e($datos['email']) ?>"
             maxlength="100" autocomplete="email" placeholder="nombre@correo.es" required <?= marca_error($errores, 'email') ?>>
      <?= error_campo($errores, 'email') ?>
    </div>
  </div>

  <label class="casilla">
    <input type="checkbox" name="privacidad" value="si" required <?= marca_error($errores, 'privacidad') ?>>
    Acepto que la biblioteca guarde mis datos para gestionar mis préstamos.
  </label>
  <?= error_campo($errores, 'privacidad') ?>

  <button type="submit" class="boton">Registrarme</button>
</form>

<?php require 'pie.php'; ?>
