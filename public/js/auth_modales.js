/**
 * ARCHIVO: auth_modales.js
 * Descripción: Controladores para validación de registro y el mapa de Leaflet
 * dentro del Modal de Registro.
 */

let mapRegister = null; let markerRegister = null;

function toggleDireccion() {
    const check = document.getElementById('checkDireccion');
    const box = document.getElementById('direccion-box');
    if (check && check.checked) {
        box.style.display = 'block';
        if (!mapRegister) initMapRegister();
        setTimeout(() => { if (mapRegister) mapRegister.invalidateSize(); }, 300);
    } else if (box) { box.style.display = 'none'; }
}

function initMapRegister() {
    if (mapRegister || !document.getElementById('mapa-container')) return;
    mapRegister = L.map('mapa-container').setView([-33.4489, -70.6693], 13);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(mapRegister);
    markerRegister = L.marker([-33.4489, -70.6693], { draggable: true }).addTo(mapRegister);
    markerRegister.on('dragend', function (e) { updateCoordsRegister(markerRegister.getLatLng()); });
    mapRegister.on('click', function (e) { markerRegister.setLatLng(e.latlng); updateCoordsRegister(e.latlng); });
}

function updateCoordsRegister(pos) {
    document.getElementById('latitud').value = pos.lat;
    document.getElementById('longitud').value = pos.lng;
}

async function buscarEnMapaRegister() {
    const direccion = document.getElementById('direccion-input').value;
    if (direccion.length < 5) return Swal.fire('Error', 'Ingresa una dirección válida.', 'warning');
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccion)}, Chile`);
        const data = await res.json();
        if (data && data.length > 0) {
            const nuevaPos = new L.LatLng(data[0].lat, data[0].lon);
            mapRegister.setView(nuevaPos, 16); markerRegister.setLatLng(nuevaPos); updateCoordsRegister(nuevaPos);
        } else { Swal.fire('Ups', 'No encontramos esa dirección.', 'info'); }
    } catch (e) { console.error(e); }
}

function formatearRut(rutInput) {
    let valor = rutInput.value.replace(/\./g, '').replace(/-/g, '');
    if (valor.length > 1) {
        let cuerpo = valor.slice(0, -1);
        let dv = valor.slice(-1).toUpperCase();
        rutInput.value = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, ".") + "-" + dv;
    }
}

document.addEventListener("DOMContentLoaded", function () {
    // Escuchar apertura del modal de registro para arreglar el tamaño del mapa
    const registerModalEl = document.getElementById('registerModal');
    if (registerModalEl) {
        registerModalEl.addEventListener('shown.bs.modal', function () {
            if (document.getElementById('checkDireccion') && document.getElementById('checkDireccion').checked && mapRegister) {
                setTimeout(() => mapRegister.invalidateSize(), 200);
            }
        });
    }

    // Validación Términos Legales
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
            const nombre = document.getElementById('reg_nombre') ? document.getElementById('reg_nombre').value.trim() : '';
            const apellido = document.getElementById('reg_apellido') ? document.getElementById('reg_apellido').value.trim() : '';
            const inputCompleto = document.getElementById('inputNombreCompleto');
            if (inputCompleto) inputCompleto.value = nombre + ' ' + apellido;
        });
    }
});