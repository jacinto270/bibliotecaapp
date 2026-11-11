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

// Mensajes pendientes de mostrar (se borran al leerlos)
$mensajes = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="es">
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
    </div>
  </header>
  <main class="contenedor">
    <h1><?= e($titulo) ?></h1>

    <?php foreach ($mensajes as $m): ?>
      <p class="<?= $m['tipo'] === 'error' ? 'error' : 'aviso' ?>" role="status"><?= e($m['texto']) ?></p>
    <?php endforeach; ?>
