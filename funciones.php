<?php
// Funciones comunes y medidas de seguridad: la incluye conexion.php,
// así que todas las páginas las tienen disponibles.

// Días que puede durar un préstamo y libros que puede tener a la vez un lector
const PLAZO_DIAS = 15;
const MAX_PRESTAMOS = 3;

// Géneros permitidos (lista blanca: no se acepta ningún otro valor)
const GENEROS = ['novela', 'poesía', 'ensayo', 'infantil', 'teatro', 'cómic', 'historia', 'ciencia'];

// ---------------------------------------------------------------------------
// Cabeceras de seguridad
// ---------------------------------------------------------------------------
// - CSP: solo se cargan scripts, estilos e imágenes de este mismo sitio
//   (bloquea scripts inyectados aunque alguien consiguiera colar HTML)
// - nosniff: el navegador no adivina tipos de archivo
// - DENY: la aplicación no se puede meter en un <iframe> (clickjacking)
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
// Que los buscadores no indexen ni sigan ninguna página de la aplicación
header('X-Robots-Tag: noindex, nofollow');

// ---------------------------------------------------------------------------
// Sesión con cookie protegida
// ---------------------------------------------------------------------------
// Solo se aceptan identificadores de sesión creados por el servidor
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'httponly' => true,     // JavaScript no puede leer la cookie
    'samesite' => 'Strict', // otras webs no pueden enviarla
    'secure' => !empty($_SERVER['HTTPS']),
]);
session_start();

// ---------------------------------------------------------------------------
// Escapar texto para HTML (evita XSS)
// ---------------------------------------------------------------------------
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

// Fecha de la base de datos (AAAA-MM-DD) en formato día/mes/año
function fecha(?string $fecha): string
{
    return $fecha ? date('d/m/Y', strtotime($fecha)) : '';
}

// ---------------------------------------------------------------------------
// Token CSRF: cada formulario POST lleva un token secreto de la sesión.
// Una web ajena no lo conoce, así que no puede enviar formularios en nombre
// del usuario.
// ---------------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function campo_csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

// Corta la petición si el token no coincide
function comprobar_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        exit('La sesión ha caducado o la petición no es válida. Vuelve atrás y recarga la página.');
    }
}

// ---------------------------------------------------------------------------
// Mensajes flash: se guardan en la sesión, se muestran una vez tras redirigir
// ---------------------------------------------------------------------------
function flash(string $tipo, string $texto): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'texto' => $texto];
}

function redirigir(string $url): never
{
    header('Location: ' . $url);
    exit;
}

// ---------------------------------------------------------------------------
// Lectura y validación de datos de formularios
// ---------------------------------------------------------------------------

// Texto de $_POST, sin espacios sobrantes; '' si no llega o no es texto
function texto_post(string $campo): string
{
    $valor = $_POST[$campo] ?? '';
    return is_string($valor) ? trim(preg_replace('/\s+/u', ' ', $valor)) : '';
}

function longitud(string $texto): int
{
    return mb_strlen($texto, 'UTF-8');
}

// Nombres y apellidos: letras (con tildes), espacios, guiones y apóstrofos
function nombre_valido(string $texto): bool
{
    return (bool) preg_match("/^[\p{L}][\p{L} '\-]*$/u", $texto);
}

// DNI o NIE español con la letra de control correcta
function dni_valido(string $dni): bool
{
    if (!preg_match('/^([0-9XYZ])([0-9]{7})([A-Z])$/', $dni, $m)) {
        return false;
    }
    $numero = strtr($m[1], ['X' => '0', 'Y' => '1', 'Z' => '2']) . $m[2];
    return 'TRWAGMYFPDXBNJZSQVHLCKE'[(int) $numero % 23] === $m[3];
}

// Teléfono español: 9 cifras empezando por 6, 7, 8 o 9 (admite +34 delante)
function telefono_normalizado(string $telefono): ?string
{
    $limpio = preg_replace('/[\s.\-]/', '', $telefono);
    $limpio = preg_replace('/^(\+34|0034)/', '', $limpio);
    return preg_match('/^[6789][0-9]{8}$/', $limpio) ? $limpio : null;
}

// ISBN-10 o ISBN-13 con el dígito de control correcto
function isbn_valido(string $isbn): bool
{
    $cifras = str_replace(['-', ' '], '', strtoupper($isbn));
    if (preg_match('/^[0-9]{13}$/', $cifras)) {
        $suma = 0;
        for ($i = 0; $i < 12; $i++) {
            $suma += (int) $cifras[$i] * ($i % 2 ? 3 : 1);
        }
        return (10 - $suma % 10) % 10 === (int) $cifras[12];
    }
    if (preg_match('/^[0-9]{9}[0-9X]$/', $cifras)) {
        $suma = 0;
        for ($i = 0; $i < 10; $i++) {
            $suma += ($cifras[$i] === 'X' ? 10 : (int) $cifras[$i]) * (10 - $i);
        }
        return $suma % 11 === 0;
    }
    return false;
}

// Estado de un préstamo sin devolver según los días que lleva
function estado_prestamo(int $dias): array
{
    if ($dias > PLAZO_DIAS) {
        return ['clase' => 'retrasado', 'icono' => '!', 'texto' => 'Retrasado ' . ($dias - PLAZO_DIAS) . ' d'];
    }
    if ($dias >= PLAZO_DIAS - 3) {
        return ['clase' => 'pronto', 'icono' => '◷', 'texto' => 'Vence pronto'];
    }
    return ['clase' => 'en-plazo', 'icono' => '✓', 'texto' => 'En plazo'];
}

// Mensaje de error debajo de un campo de formulario, si lo tiene
function error_campo(array $errores, string $campo): string
{
    return isset($errores[$campo])
        ? '<p class="error-campo" id="error-' . $campo . '">' . e($errores[$campo]) . '</p>'
        : '';
}

// Atributos de accesibilidad de un campo con error
function marca_error(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? 'aria-invalid="true" aria-describedby="error-' . $campo . '"' : '';
}

// ---------------------------------------------------------------------------
// Usuarios y control de acceso
// ---------------------------------------------------------------------------
// Todas las páginas exigen haber iniciado sesión, salvo las que definen
// PAGINA_PUBLICA antes de incluir conexion.php (entrar.php y crear_cuenta.php).
// Así, una página nueva queda protegida aunque se nos olvide.

const ROLES = ['admin', 'bibliotecario'];
const INACTIVIDAD_MAX = 2 * 60 * 60;   // la sesión caduca tras 2 horas sin uso
const INTENTOS_MAX = 5;                 // intentos fallidos de entrar...
const INTENTOS_MINUTOS = 15;            // ...en estos minutos, antes de bloquear
const CLAVE_MIN = 10;                   // longitud mínima de la contraseña

// Termina la sesión y cambia su identificador
function cerrar_sesion(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

// Usuario con la sesión iniciada, o null. Se comprueba en la base de datos
// en cada petición: si un admin lo desactiva, pierde el acceso al momento.
function usuario_actual(PDO $pdo): ?array
{
    $id = $_SESSION['usuario_id'] ?? null;
    if (!is_int($id)) {
        return null;
    }
    if (time() - ($_SESSION['ultimo_uso'] ?? 0) > INACTIVIDAD_MAX) {
        cerrar_sesion();
        flash('error', 'La sesión ha caducado por inactividad. Vuelve a entrar.');
        return null;
    }
    $consulta = $pdo->prepare('SELECT id, usuario, nombre, rol FROM usuarios WHERE id = ? AND activo = 1');
    $consulta->execute([$id]);
    $usuario = $consulta->fetch();
    if (!$usuario) {
        cerrar_sesion();
        return null;
    }
    $_SESSION['ultimo_uso'] = time();
    return $usuario;
}

// Solo para administradores
function requerir_admin(?array $usuario): void
{
    if (($usuario['rol'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('No tienes permiso para ver esta página.');
    }
}

// Nombre de usuario: minúsculas, números, punto, guion y guion bajo (3 a 30)
function usuario_valido(string $usuario): bool
{
    return (bool) preg_match('/^[a-z0-9._-]{3,30}$/', $usuario);
}

// IP del visitante (para limitar intentos)
function ip_cliente(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

// ¿Se han superado $max intentos de este tipo en los últimos $minutos?
// Se cuenta por IP y, si se indica, también por nombre de usuario.
function demasiados_intentos(PDO $pdo, string $tipo, int $max, int $minutos, string $usuario = ''): bool
{
    $consulta = $pdo->prepare(
        'SELECT COUNT(*) FROM intentos
         WHERE tipo = ? AND momento > NOW() - INTERVAL ? MINUTE AND (ip = ? OR (? <> \'\' AND usuario = ?))'
    );
    $consulta->execute([$tipo, $minutos, ip_cliente(), $usuario, $usuario]);
    return (int) $consulta->fetchColumn() >= $max;
}

function anotar_intento(PDO $pdo, string $tipo, string $usuario = ''): void
{
    $pdo->prepare('INSERT INTO intentos (tipo, ip, usuario, momento) VALUES (?, ?, ?, NOW())')
        ->execute([$tipo, ip_cliente(), substr($usuario, 0, 30)]);
    // Limpieza: los intentos de hace más de un día ya no sirven
    $pdo->exec('DELETE FROM intentos WHERE momento < NOW() - INTERVAL 1 DAY');
}

$usuarioActual = isset($pdo) ? usuario_actual($pdo) : null;

if (!defined('PAGINA_PUBLICA') && !$usuarioActual) {
    // Recordar la página pedida (solo si es una página de la aplicación) para volver tras entrar
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $pedida = basename($_SERVER['SCRIPT_NAME']) . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');
        if (preg_match('/^[a-z_]+\.php(\?[^\s]*)?$/', $pedida)) {
            $_SESSION['volver'] = $pedida;
        }
    }
    redirigir('entrar.php');
}
