// Pide confirmación antes de enviar los formularios marcados con data-confirmar.
// Va en un archivo aparte porque la política CSP no permite JavaScript en línea.
document.addEventListener('submit', function (evento) {
  const mensaje = evento.target.dataset.confirmar;
  if (mensaje && !window.confirm(mensaje)) {
    evento.preventDefault();
  }
});
