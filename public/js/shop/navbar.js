/**
 * ARCHIVO: navbar.js
 * Descripción: Manejo de geolocalización y cambio de sucursal.
 * (Nota: El fix de modales se maneja centralizado en scripts_globales.php)
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. LEER CONFIGURACIÓN INYECTADA
    const config = window.NavbarConfig || {};
    const comunaCargada = config.comunaCargada || false;
    const yaIntentoGeoloc = sessionStorage.getItem('geoloc_intentada');

    // 2. DISPARAR GEOLOCALIZACIÓN SI CORRESPONDE
    if (!comunaCargada && !yaIntentoGeoloc) {
        sessionStorage.setItem('geoloc_intentada', 'true');
        // Asegúrate de que solicitarGeolocalizacion() esté definida globalmente
        if (typeof solicitarGeolocalizacion === 'function') {
            solicitarGeolocalizacion();
        }
    }
});

// =========================================================
// FUNCIONES DE GEOLOCALIZACIÓN Y COMUNAS
// =========================================================

function solicitarGeolocalizacion() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                fetch(window.BASE_URL + 'location/detectar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `lat=${pos.coords.latitude}&lng=${pos.coords.longitude}`
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            cambiarComunaDirecto(data.comuna_id, data.comuna_nombre, 29);
                        }
                    })
                    .catch(err => console.error("Error detectando ubicación:", err));
            },
            (error) => {
                console.warn("GPS denegado, asignando La Calera por defecto.");
                cambiarComunaDirecto(63, 'La Calera', 29);
            }
        );
    } else {
        cambiarComunaDirecto(63, 'La Calera', 29);
    }
}

function cambiarComunaDirecto(id, nombre, sucursal) {
    // 🔥 PASO CLAVE: Quitamos el foco del botón que se pinchó 
    // para evitar el error de aria-hidden durante el reload
    if (document.activeElement) {
        document.activeElement.blur();
    }

    fetch(window.BASE_URL + 'location/actualizar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `comuna_id=${id}&nombre=${nombre}&sucursal_id=${sucursal}`
    })
        .then(() => {
            // Recargamos solo después de asegurarnos que el foco se perdió
            window.location.reload();
        });
}

function cambiarComunaRapida(nombre, sucursal, confirmado = 0) {
    const formData = new FormData();
    formData.append('nombre', nombre);
    formData.append('sucursal_id', sucursal);
    formData.append('confirmado', confirmado);

    fetch(window.BASE_URL + 'location/actualizar_por_nombre', {
        method: 'POST',
        body: formData
    })
        .then(res => {
            if (!res.ok) throw new Error('Error en la red');
            return res.json();
        })
        .then(data => {
            if (data.status === 'requiere_confirmacion') {
                // Caso especial: Confirmación de cambio de sucursal (Peñablanca/VA)
                Swal.fire({
                    icon: 'warning',
                    title: data.titulo,
                    html: data.html,
                    showCancelButton: true,
                    confirmButtonColor: '#2A1B5E',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, cambiar y ajustar',
                    cancelButtonText: 'No, me quedo donde estoy',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        cambiarComunaRapida(nombre, sucursal, 1);
                    }
                });
            } else if (data.status === 'success') {
                location.reload();
            } else {
                Swal.fire('Error', 'No pudimos cambiar la comuna', 'error');
            }
        })
        .catch(err => {
            console.error("Error cambiando comuna:", err);
            // Evitamos mostrar error si es por el refresh de la página
        });
}