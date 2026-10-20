// ============================================================
// assets/js/app.js - JavaScript básico de BiblioTech
// ============================================================
document.addEventListener('DOMContentLoaded', function () {

    // 1) Botón del menú en pantallas pequeñas (celular)
    var botonMenu = document.getElementById('boton-menu');
    var menu = document.getElementById('menu');
    if (botonMenu && menu) {
        botonMenu.addEventListener('click', function () {
            menu.classList.toggle('activo');
        });
    }

    // 2) Confirmación antes de eliminar registros
    //    Se aplica a los formularios con la clase "confirmar-eliminar"
    document.querySelectorAll('form.confirmar-eliminar').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            var mensaje = formulario.getAttribute('data-mensaje')
                || '¿Seguro que quieres eliminar este registro?';
            if (!confirm(mensaje)) {
                evento.preventDefault(); // El usuario canceló
            }
        });
    });

    // 3) Los mensajes de éxito/error se ocultan solos a los 6 segundos
    document.querySelectorAll('.alerta').forEach(function (alerta) {
        setTimeout(function () {
            alerta.classList.add('alerta-oculta');
        }, 6000);
    });
});
