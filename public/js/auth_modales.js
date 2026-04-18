/**
 * ARCHIVO: auth_modales.js
 * Descripción: Controladores para validación de registro y el mapa de Leaflet
 * dentro del Modal de Registro.
 */

// 1. USAMOS 'VAR' PARA EVITAR EL ERROR DE DECLARACIÓN DUPLICADA 🚀
var mapRegister = null; 
var markerRegister = null;

// 2. Función para mostrar/ocultar la caja de dirección
function toggleDireccion() {
    const check = document.getElementById('reg-check-direccion');
    const box = document.getElementById('reg-wrapper-direccion');
    
    if (check && check.checked) {
        box.classList.remove('d-none');
        // Inicializar el mapa si no existe
        if (!mapRegister) initMapRegister();
        // Recalcular tamaño después de que la animación de Bootstrap termine
        setTimeout(() => { if (mapRegister) mapRegister.invalidateSize(); }, 300);
    } else if (box) { 
        box.classList.add('d-none'); 
    }
}

// 3. Inicializar el mapa
function initMapRegister() {
    // Apuntamos al ID correcto de tu modal: reg-map
    if (mapRegister || !document.getElementById('reg-map')) return;
    
    // Coordenadas centradas en la V Región
    mapRegister = L.map('reg-map').setView([-33.0442, -71.4011], 13);
    
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { 
        maxZoom: 19,
        attribution: '&copy; Cencocal'
    }).addTo(mapRegister);
    
    markerRegister = L.marker([-33.0442, -71.4011], { draggable: true }).addTo(mapRegister);
    
    markerRegister.on('dragend', function (e) { updateCoordsRegister(markerRegister.getLatLng()); });
    mapRegister.on('click', function (e) { 
        markerRegister.setLatLng(e.latlng); 
        updateCoordsRegister(e.latlng); 
    });
}

// 4. Actualizar inputs ocultos
function updateCoordsRegister(pos) {
    document.getElementById('reg-lat').value = pos.lat;
    document.getElementById('reg-lng').value = pos.lng;
}

// 5. Buscar dirección en el mapa
async function buscarEnMapaRegister() {
    const calle = document.getElementById('reg-calle').value;
    const numero = document.getElementById('reg-numero').value;
    const comuna = document.getElementById('reg-select-comuna').value;

    if (!calle || !comuna) {
        return Swal.fire('Faltan Datos', 'Ingresa la calle y selecciona una comuna.', 'warning');
    }

    const direccionCompleta = `${calle} ${numero}, ${comuna}, Chile`;
    
    const btn = document.getElementById('btn-buscar-direccion');
    const oldHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    btn.disabled = true;

    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccionCompleta)}`);
        const data = await res.json();
        
        if (data && data.length > 0) {
            const nuevaPos = new L.LatLng(data[0].lat, data[0].lon);
            mapRegister.setView(nuevaPos, 16); 
            markerRegister.setLatLng(nuevaPos); 
            updateCoordsRegister(nuevaPos);
        } else { 
            Swal.fire('Ups', 'No encontramos esa dirección exacta. Por favor, arrastra el pin rojo manualmente en el mapa.', 'info'); 
        }
    } catch (e) { 
        console.error(e); 
    } finally {
        btn.innerHTML = oldHtml;
        btn.disabled = false;
    }
}

// 6. Event Listeners cuando el DOM está listo
document.addEventListener("DOMContentLoaded", function () {
    
    // Escuchar el switch de "Quiero agregar dirección"
    const checkDireccion = document.getElementById('reg-check-direccion');
    if (checkDireccion) {
        checkDireccion.addEventListener('change', toggleDireccion);
    }

    // Escuchar el botón de la lupa para buscar en el mapa
    const btnBuscar = document.getElementById('btn-buscar-direccion');
    if (btnBuscar) {
        btnBuscar.addEventListener('click', buscarEnMapaRegister);
    }

    // Mensaje bonito al Confirmar Dirección
    const btnGuardarDir = document.getElementById('btn-guardar-direccion-ui');
    if (btnGuardarDir) {
        btnGuardarDir.addEventListener('click', function() {
            Swal.fire({
                toast: true, position: 'top-end', icon: 'success', 
                title: 'Dirección fijada en el mapa', showConfirmButton: false, timer: 2500
            });
        });
    }

    // Escuchar apertura del modal de registro para arreglar el gris del mapa
    const registerModalEl = document.getElementById('registerModal');
    if (registerModalEl) {
        registerModalEl.addEventListener('shown.bs.modal', function () {
            if (checkDireccion && checkDireccion.checked && mapRegister) {
                setTimeout(() => mapRegister.invalidateSize(), 200);
            }
        });
    }

    // Validación Términos Legales de Cencocal
    const formRegistro = document.getElementById('formRegistro');
    if (formRegistro) {
        formRegistro.addEventListener('submit', function (e) {
            const checkTerms = document.getElementById('checkTerms');
            if (checkTerms && !checkTerms.checked) {
                e.preventDefault();
                Swal.fire({
                    imageUrl: window.BASE_URL + 'img/cencocalin/cencocalin_abogado.png',
                    imageHeight: 150, title: 'Un momento...', text: 'Nuestros abogados dicen que debes aceptar los Términos y Condiciones.',
                    confirmButtonText: 'Leer y Aceptar', confirmButtonColor: '#2A1B5E'
                });
            }
            // Armamos el nombre completo silenciosamente antes de enviar a la BD
            const nombre = document.getElementById('reg_nombre') ? document.getElementById('reg_nombre').value.trim() : '';
            const apellido = document.getElementById('reg_apellido') ? document.getElementById('reg_apellido').value.trim() : '';
            const inputCompleto = document.getElementById('inputNombreCompleto');
            if (inputCompleto) inputCompleto.value = nombre + ' ' + apellido;
        });
    }

    // FIX: Permite que se pueda escribir en SweetAlert cuando hay un modal de Bootstrap abierto
document.addEventListener('focusin', (e) => {
    if (e.target.closest('.swal2-container')) {
        e.stopImmediatePropagation();
    }
});
});
