<?php
// Página pública: inicio de sesión
const PAGINA_PUBLICA = true;
require 'conexion.php';

if ($usuarioActual) {
    redirigir('index.php');
}

$error = '';
$usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprobar_csrf();

    $usuario = mb_strtolower(texto_post('usuario'), 'UTF-8');
    $clave = $_POST['clave'] ?? '';
    $clave = is_string($clave) ? $clave : '';

    // Freno a la fuerza bruta: demasiados fallos desde esta IP o contra este usuario
    if (demasiados_intentos($pdo, 'entrar', INTENTOS_MAX, INTENTOS_MINUTOS, $usuario)) {
        $error = 'Demasiados intentos fallidos. Espera ' . INTENTOS_MINUTOS . ' minutos y vuelve a probar.';
    } else {
        $consulta = $pdo->prepare('SELECT id, nombre, clave, activo FROM usuarios WHERE usuario = ?');
        $consulta->execute([$usuario]);
        $cuenta = $consulta->fetch();

        // Si el usuario no existe se comprueba igualmente una contraseña falsa,
        // para que la respuesta tarde lo mismo y no delate qué usuarios existen
        $hash = $cuenta['clave'] ?? password_hash('clave-inexistente', PASSWORD_DEFAULT);
        $correcta = password_verify($clave, $hash) && $cuenta;

        if (!$correcta) {
            anotar_intento($pdo, 'entrar', $usuario);
            $error = 'Usuario o contraseña incorrectos.';
        } elseif (!$cuenta['activo']) {
            $error = 'Tu cuenta todavía no está activada: un administrador tiene que aprobarla.';
        } else {
            // Nuevo identificador de sesión y nuevo token CSRF (evita la fijación de sesión)
            session_regenerate_id(true);
            unset($_SESSION['csrf']);
            $_SESSION['usuario_id'] = (int) $cuenta['id'];
            $_SESSION['ultimo_uso'] = time();

            if (password_needs_rehash($cuenta['clave'], PASSWORD_DEFAULT)) {
                $pdo->prepare('UPDATE usuarios SET clave = ? WHERE id = ?')
                    ->execute([password_hash($clave, PASSWORD_DEFAULT), $cuenta['id']]);
            }
            $pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?')->execute([$cuenta['id']]);

            $volver = $_SESSION['volver'] ?? 'index.php';
            unset($_SESSION['volver']);
            flash('aviso', 'Hola, ' . $cuenta['nombre'] . '.');
            redirigir($volver);
        }
    }
}

$hayUsuarios = (bool) $pdo->query('SELECT 1 FROM usuarios LIMIT 1')->fetchColumn();

$titulo = 'Entrar';
require 'cabecera.php';
?>

<div class="acceso">
  <?php if (!$hayUsuarios): ?>
    <p class="aviso">Todavía no hay ninguna cuenta. <a href="crear_cuenta.php">Crea la primera</a>: será la de administrador.</p>
  <?php endif; ?>

  <?php if ($error): ?>
    <p class="error" role="alert"><?= e($error) ?></p>
  <?php endif; ?>

  <form method="post" class="tarjeta formulario">
    <?= campo_csrf() ?>
    <label for="usuario">Usuario</label>
    <input type="text" name="usuario" id="usuario" value="<?= e($usuario) ?>" maxlength="30"
           autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>

    <label for="clave">Contraseña</label>
    <input type="password" name="clave" id="clave" maxlength="72" autocomplete="current-password" required>

    <button type="submit" class="boton">Entrar</button>
  </form>

  <p class="ayuda">¿No tienes cuenta? <a href="crear_cuenta.php">Solicítala aquí</a>.</p>
</div>

<?php require 'pie.php'; ?>
