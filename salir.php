<?php
// Cerrar sesión: solo por POST con token CSRF (un enlace ajeno no puede echarte)
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('index.php');
}
comprobar_csrf();

cerrar_sesion();
flash('aviso', 'Has cerrado la sesión.');
redirigir('entrar.php');
