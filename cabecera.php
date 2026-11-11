<?php
// Página actual, para marcar su enlace en el menú
$actual = basename($_SERVER['SCRIPT_NAME']);

$menu = [
    'index.php' => 'Préstamos',
    'nuevo.php' => 'Prestar',
    'libros.php' => 'Libros',
    'lectores.php' => 'Lectores',
    'estadisticas.php' => 'Estadísticas',
];

// Páginas que cuelgan de otra sección del menú
$seccion = [
    'libro_nuevo.php' => 'libros.php',
    'registro.php' => 'lectores.php',
    'lector.php' => 'lectores.php',
][$actual] ?? $actual;

// Tema elegido (cookie que guarda app.js): claro, oscuro o sistema.
// Se pone en <html> desde PHP para que la página no parpadee al cargar.
$tema = $_COOKIE['tema'] ?? 'sistema';
$tema = in_array($tema, ['claro', 'oscuro', 'sistema'], true) ? $tema : 'sistema';
$temas = ['claro' => ['☀', 'Claro'], 'sistema' => ['◐', 'Sistema'], 'oscuro' => ['☾', 'Oscuro']];

// Mensajes pendientes de mostrar (se borran al leerlos)
$mensajes = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="es" data-tema="<?= $tema ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo) ?> · Biblioteca municipal</title>
  <link rel="icon" href="logo.svg" type="image/svg+xml">
  <link rel="stylesheet" href="estilos.css">
  <script src="app.js" defer></script>
</head>
<body>
  <header class="cabecera">
    <div class="contenedor cabecera__fila">
      <a class="marca" href="index.php">
        <img src="logo.svg" alt="" width="44" height="44">
        <span>
          <strong>Biblioteca municipal</strong>
          <small>Leer nos une</small>
        </span>
      </a>
      <nav aria-label="Menú principal">
        <?php foreach ($menu as $url => $texto): ?>
          <a href="<?= $url ?>" <?= $url === $seccion ? 'aria-current="page"' : '' ?>><?= $texto ?></a>
        <?php endforeach; ?>
      </nav>
      <div class="tema" role="group" aria-label="Tema de colores">
        <?php foreach ($temas as $valor => [$icono, $texto]): ?>
          <button type="button" data-tema-opcion="<?= $valor ?>" aria-pressed="<?= $valor === $tema ? 'true' : 'false' ?>"
                  title="Tema <?= mb_strtolower($texto) ?>">
            <span aria-hidden="true"><?= $icono ?></span><span class="tema__texto"><?= $texto ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </header>
  <main class="contenedor">
    <h1><?= e($titulo) ?></h1>

    <?php foreach ($mensajes as $m): ?>
      <p class="<?= $m['tipo'] === 'error' ? 'error' : 'aviso' ?>" role="status"><?= e($m['texto']) ?></p>
    <?php endforeach; ?>
