// Pide confirmación antes de enviar los formularios marcados con data-confirmar.
// Va en un archivo aparte porque la política CSP no permite JavaScript en línea.
document.addEventListener('submit', function (evento) {
  const mensaje = evento.target.dataset.confirmar;
  if (mensaje && !window.confirm(mensaje)) {
    evento.preventDefault();
  }
});

// Selector de tema (claro, oscuro o el del sistema). La elección se guarda
// en una cookie para que PHP la ponga en <html data-tema> en la siguiente carga.
document.addEventListener('click', function (evento) {
  const boton = evento.target.closest('[data-tema-opcion]');
  if (!boton) {
    return;
  }
  const tema = boton.dataset.temaOpcion;
  document.documentElement.dataset.tema = tema;
  document.cookie = 'tema=' + tema + '; path=/; max-age=31536000; samesite=strict';
  document.querySelectorAll('[data-tema-opcion]').forEach(function (b) {
    b.setAttribute('aria-pressed', b === boton ? 'true' : 'false');
  });
});
