<?php
require 'conexion.php';

$errores = [];
$datos = ['isbn' => '', 'titulo' => '', 'autor' => '', 'anio' => '', 'genero' => ''];
$anioActual = (int) date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    comprobar_csrf();

    $datos['isbn'] = strtoupper(texto_post('isbn'));
    $datos['titulo'] = texto_post('titulo');
    $datos['autor'] = texto_post('autor');
    $datos['anio'] = texto_post('anio');
    $datos['genero'] = texto_post('genero');

    if (longitud($datos['isbn']) > 17 || !isbn_valido($datos['isbn'])) {
        $errores['isbn'] = 'El ISBN no es válido: revisa las cifras (10 o 13, con o sin guiones).';
    }

    if ($datos['titulo'] === '' || longitud($datos['titulo']) > 100) {
        $errores['titulo'] = 'Escribe el título (máximo 100 caracteres).';
    }

    if ($datos['autor'] === '' || longitud($datos['autor']) > 80) {
        $errores['autor'] = 'Escribe el autor (máximo 80 caracteres).';
    }

    // El año es opcional, pero si se escribe tiene que ser razonable
    $anio = null;
    if ($datos['anio'] !== '') {
        $anio = filter_var($datos['anio'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1000, 'max_range' => $anioActual],
        ]);
        if ($anio === false) {
            $errores['anio'] = "El año tiene que estar entre 1000 y $anioActual.";
        }
    }

    // Solo se aceptan los géneros de la lista
    if (!in_array($datos['genero'], GENEROS, true)) {
        $errores['genero'] = 'Elige un género de la lista.';
    }

    if (!$errores) {
        try {
            $alta = $pdo->prepare(
                'INSERT INTO libros (isbn, titulo, autor, anio, genero) VALUES (?, ?, ?, ?, ?)'
            );
            $alta->execute([$datos['isbn'], $datos['titulo'], $datos['autor'], $anio, $datos['genero']]);

            flash('aviso', '«' . $datos['titulo'] . '» se ha añadido al catálogo.');
            redirigir('libros.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errores['isbn'] = 'Ya hay un libro con ese ISBN en el catálogo.';
            } else {
                $errores['general'] = 'No se ha podido añadir el libro.';
            }
        }
    }
}

$titulo = 'Añadir libro';
require 'cabecera.php';
?>

<?php if ($errores): ?>
  <div class="error" role="alert">
    <?= isset($errores['general']) ? e($errores['general']) : 'Revisa los campos marcados en rojo.' ?>
  </div>
<?php endif; ?>

<form method="post" class="tarjeta formulario" novalidate>
  <?= campo_csrf() ?>

  <div class="rejilla">
    <div class="rejilla__ancho">
      <label for="titulo">Título</label>
      <input type="text" name="titulo" id="titulo" value="<?= e($datos['titulo']) ?>"
             maxlength="100" required <?= marca_error($errores, 'titulo') ?>>
      <?= error_campo($errores, 'titulo') ?>
    </div>

    <div class="rejilla__ancho">
      <label for="autor">Autor</label>
      <input type="text" name="autor" id="autor" value="<?= e($datos['autor']) ?>"
             maxlength="80" required <?= marca_error($errores, 'autor') ?>>
      <?= error_campo($errores, 'autor') ?>
    </div>

    <div>
      <label for="isbn">ISBN</label>
      <input type="text" name="isbn" id="isbn" value="<?= e($datos['isbn']) ?>"
             maxlength="17" placeholder="978-84-376-0494-7" required <?= marca_error($errores, 'isbn') ?>>
      <?= error_campo($errores, 'isbn') ?>
    </div>

    <div>
      <label for="anio">Año de publicación <span class="secundario">(opcional)</span></label>
      <input type="number" name="anio" id="anio" value="<?= e($datos['anio']) ?>"
             min="1000" max="<?= $anioActual ?>" <?= marca_error($errores, 'anio') ?>>
      <?= error_campo($errores, 'anio') ?>
    </div>

    <div>
      <label for="genero">Género</label>
      <select name="genero" id="genero" required <?= marca_error($errores, 'genero') ?>>
        <option value="">Elige un género…</option>
        <?php foreach (GENEROS as $g): ?>
          <option value="<?= e($g) ?>" <?= $g === $datos['genero'] ? 'selected' : '' ?>><?= e(ucfirst($g)) ?></option>
        <?php endforeach; ?>
      </select>
      <?= error_campo($errores, 'genero') ?>
    </div>
  </div>

  <button type="submit" class="boton">Añadir al catálogo</button>
</form>

<?php require 'pie.php'; ?>
