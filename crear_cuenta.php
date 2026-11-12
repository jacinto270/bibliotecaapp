<?php
// Página pública: solicitud de cuenta de usuario.
// La primera cuenta que se crea es la de administrador y queda activa;
// las demás quedan pendientes hasta que un administrador las active.
const PAGINA_PUBLICA = true;
require 'conexion.php';

if ($usuarioActual) {
    redirigir('index.php');
}

$errores = [];
$datos = ['nombre' => '', 'usuario' => ''];
$creada = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprobar_csrf();

    // Trampas para robots: campo oculto que una persona no rellena,
    // tiempo mínimo para rellenar el formulario y pocas altas por IP
    $esRobot = texto_post('web') !== ''
        || time() - ($_SESSION['alta_inicio'] ?? 0) < 4;

    $datos['nombre'] = texto_post('nombre');
    $datos['usuario'] = mb_strtolower(texto_post('usuario'), 'UTF-8');
    $clave = $_POST['clave'] ?? '';
    $clave2 = $_POST['clave2'] ?? '';
    $clave = is_string($clave) ? $clave : '';
    $clave2 = is_string($clave2) ? $clave2 : '';

    if ($esRobot) {
        $errores['general'] = 'No se ha podido crear la cuenta. Inténtalo de nuevo.';
    } elseif (demasiados_intentos($pdo, 'alta', 3, 60)) {
        $errores['general'] = 'Se han pedido demasiadas cuentas desde esta conexión. Inténtalo más tarde.';
    }

    if ($datos['nombre'] === '' || longitud($datos['nombre']) > 60 || !nombre_valido($datos['nombre'])) {
        $errores['nombre'] = 'Escribe tu nombre (solo letras, espacios y guiones; máximo 60).';
    }

    if (!usuario_valido($datos['usuario'])) {
        $errores['usuario'] = 'De 3 a 30 caracteres: letras minúsculas sin tildes, números, punto, guion o guion bajo.';
    }

    if (longitud($clave) < CLAVE_MIN) {
        $errores['clave'] = 'La contraseña tiene que tener al menos ' . CLAVE_MIN . ' caracteres.';
    } elseif (strlen($clave) > 72) {
        $errores['clave'] = 'La contraseña es demasiado larga (máximo 72 caracteres).';
    } elseif (mb_strtolower($clave, 'UTF-8') === $datos['usuario']) {
        $errores['clave'] = 'La contraseña no puede ser igual que el usuario.';
    } elseif ($clave !== $clave2) {
        $errores['clave2'] = 'Las dos contraseñas no coinciden.';
    }

    if (!$errores) {
        anotar_intento($pdo, 'alta', $datos['usuario']);
        try {
            // ¿Es la primera cuenta? Entonces es la del administrador
            $primera = !$pdo->query('SELECT 1 FROM usuarios LIMIT 1')->fetchColumn();

            $pdo->prepare(
                'INSERT INTO usuarios (usuario, nombre, clave, rol, activo, creado) VALUES (?, ?, ?, ?, ?, NOW())'
            )->execute([
                $datos['usuario'],
                $datos['nombre'],
                password_hash($clave, PASSWORD_DEFAULT),
                $primera ? 'admin' : 'bibliotecario',
                $primera ? 1 : 0,
            ]);

            unset($_SESSION['alta_inicio']);
            if ($primera) {
                flash('aviso', 'Cuenta de administrador creada. Ya puedes entrar.');
                redirigir('entrar.php');
            }
            $creada = true;
        } catch (PDOException $e) {
            $errores[$e->getCode() === '23000' ? 'usuario' : 'general'] = $e->getCode() === '23000'
                ? 'Ese nombre de usuario ya está cogido.'
                : 'No se ha podido crear la cuenta.';
        }
    }
}

// Momento en que se muestra el formulario (para la trampa anti-robots)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['alta_inicio'])) {
    $_SESSION['alta_inicio'] = time();
}

$titulo = 'Crear cuenta';
require 'cabecera.php';
?>

<div class="acceso">
  <?php if ($creada): ?>
    <p class="aviso" role="status">Cuenta solicitada. Podrás entrar cuando un administrador la active.</p>
    <p><a class="boton boton--secundario" href="entrar.php">Volver a Entrar</a></p>
  <?php else: ?>

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

      <label for="nombre">Nombre</label>
      <input type="text" name="nombre" id="nombre" value="<?= e($datos['nombre']) ?>" maxlength="60"
             autocomplete="name" required <?= marca_error($errores, 'nombre') ?>>
      <?= error_campo($errores, 'nombre') ?>

      <label for="usuario">Usuario</label>
      <input type="text" name="usuario" id="usuario" value="<?= e($datos['usuario']) ?>" maxlength="30"
             autocomplete="username" autocapitalize="none" spellcheck="false" required <?= marca_error($errores, 'usuario') ?>>
      <?= error_campo($errores, 'usuario') ?>

      <label for="clave">Contraseña</label>
      <input type="password" name="clave" id="clave" minlength="<?= CLAVE_MIN ?>" maxlength="72"
             autocomplete="new-password" required <?= marca_error($errores, 'clave') ?>>
      <?= error_campo($errores, 'clave') ?>
      <p class="ayuda">Mínimo <?= CLAVE_MIN ?> caracteres. Mejor una frase que una palabra.</p>

      <label for="clave2">Repite la contraseña</label>
      <input type="password" name="clave2" id="clave2" maxlength="72"
             autocomplete="new-password" required <?= marca_error($errores, 'clave2') ?>>
      <?= error_campo($errores, 'clave2') ?>

      <button type="submit" class="boton">Crear cuenta</button>
    </form>

    <p class="ayuda">¿Ya tienes cuenta? <a href="entrar.php">Entra</a>.</p>
  <?php endif; ?>
</div>

<?php require 'pie.php'; ?>
