/**
 * Lógica del Checkout
 * Requiere: Leaflet, SweetAlert2 y el objeto global CheckoutConfig definido en la vista.
 */

// Variables Globales de Mapas
let mapNueva = null, markerNueva = null; // Mapa para nueva dirección
let mapRetiro = null;                    // Mapa para retiro en sucursal
let mapSaved = null;                     // Mapa para dirección guardada (solo visualización)

// Utilidad: Formateador de moneda CLP
const formatCLP = (num) => '$' + num.toLocaleString('es-CL');

document.addEventListener("DOMContentLoaded", function () {
    // 1. Inicializar estado de la página
    cambiarMetodoEntrega();

    // 2. Si despacho está marcado por defecto, cargar lógica de dirección
    const radioDespacho = document.getElementById('opcion_despacho');
    if (radioDespacho && radioDespacho.checked) {
        gestionarNuevaDireccion();
    }

    // 3. Bloquear tecla Enter en inputs (evita envío accidental del form)
    document.addEventListener("keydown", e => {
        if (e.key === "Enter" && e.target.tagName !== "TEXTAREA") e.preventDefault();
    });
});

// =========================================================
// 1. LÓGICA PRINCIPAL (CAMBIO DE PESTAÑA Y COSTOS)
// =========================================================

function cambiarMetodoEntrega() {
    const esRetiro = document.getElementById('opcion_retiro').checked;

    // Elementos DOM
    const bloqueRetiro = document.getElementById('bloque_retiro');
    const bloqueDespacho = document.getElementById('bloque_despacho');
    const alertaEnvio = document.getElementById('alerta-envio-gratis');
    const labelEnvio = document.getElementById('resumen-costo-envio');
    const labelTotal = document.getElementById('resumen-total-final');
    const inputSucursal = document.getElementById('input_sucursal_codigo');

    // Alternar visibilidad de bloques
    bloqueRetiro.classList.toggle('d-none', !esRetiro);
    bloqueDespacho.classList.toggle('d-none', esRetiro);

    let costoFinal = 0;

    if (esRetiro) {
        // --- CASO RETIRO ---
        costoFinal = 0;
        if (alertaEnvio) alertaEnvio.classList.add('d-none');

        inputSucursal.disabled = false; // Habilitar input hidden para que se envíe

        // Si ya hay comuna seleccionada, intentar renderizar mapa
        const comboComuna = document.getElementById('filtro_comuna_retiro');
        if (comboComuna && comboComuna.value) {
            gestionarRetiroAutomatico();
        }

    } else {
        // --- CASO DESPACHO ---
        const { totalCarro, umbralGratis, umbralAlerta, costoDespacho } = CheckoutConfig;

        if (totalCarro >= umbralGratis) {
            costoFinal = 0;
            if (alertaEnvio) alertaEnvio.classList.add('d-none');
        } else {
            costoFinal = costoDespacho;
            if (totalCarro >= umbralAlerta && alertaEnvio) {
                alertaEnvio.classList.remove('d-none');
            }
        }

        inputSucursal.disabled = true; // Deshabilitar input de sucursal
        gestionarNuevaDireccion();     // Asegurar visualización correcta del mapa de dirección
    }

    // Actualizar Resumen de Precios
    labelEnvio.innerHTML = (costoFinal === 0)
        ? '<span class="text-success fw-bold">GRATIS</span>'
        : formatCLP(costoFinal);

    labelTotal.innerText = formatCLP(CheckoutConfig.totalCarro + costoFinal);
}

// =========================================================
// 2. LÓGICA RETIRO AUTOMÁTICO (1:1 COMUNA -> SUCURSAL)
// =========================================================

function gestionarRetiroAutomatico() {
    const comunaSel = document.getElementById('filtro_comuna_retiro').value;
    const container = document.getElementById('contenedor-detalle-sucursal');
    const inputHidden = document.getElementById('input_sucursal_codigo');

    if (!comunaSel) {
        container.classList.add('d-none');
        inputHidden.value = "";
        return;
    }

    // Buscar sucursal en el array de configuración
    // Usamos toLowerCase para hacer la búsqueda flexible
    const sucursal = CheckoutConfig.sucursales.find(s =>
        s.comuna.toLowerCase().includes(comunaSel.toLowerCase()) ||
        s.direccion.toLowerCase().includes(comunaSel.toLowerCase())
    );

    if (sucursal) {
        // Llenar datos en la tarjeta
        document.getElementById('card-sucursal-nombre').innerText = sucursal.nombre;
        document.getElementById('card-sucursal-dir').innerText = sucursal.direccion;
        document.getElementById('card-sucursal-horario').innerText = sucursal.horario;
        document.getElementById('card-sucursal-fono').innerText = sucursal.fono || 'No disponible';

        // Asignar valor al input hidden (vital para el backend)
        inputHidden.value = sucursal.codigo;

        container.classList.remove('d-none');

        // Renderizar Mapa (con pequeño delay para asegurar visibilidad del contenedor)
        setTimeout(() => {
            if (mapRetiro) { mapRetiro.remove(); mapRetiro = null; }

            // Coordenadas o default
            let lat = sucursal.lat || -32.7833;
            let lng = sucursal.lng || -71.2000;

            mapRetiro = L.map('mapa-retiro-inline').setView([lat, lng], 16);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(mapRetiro);

            const redIcon = new L.Icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
            });

            L.marker([lat, lng], { icon: redIcon }).addTo(mapRetiro).bindPopup(sucursal.nombre);
        }, 300);

    } else {
        Swal.fire('Ups', 'No encontramos tienda habilitada en esta comuna.', 'info');
        container.classList.add('d-none');
        inputHidden.value = "";
    }
}

// =========================================================
// 3. LÓGICA DESPACHO (DIRECCIONES)
// =========================================================
function gestionarNuevaDireccion() {
    const selector = document.getElementById('selector_direccion');
    const formNueva = document.getElementById('form_nueva_direccion');
    const cardGuardada = document.getElementById('contenedor-detalle-direccion');

    if (!selector) return;

    const option = selector.options[selector.selectedIndex];

    // --- CAMBIO CLAVE: Capturar lat/lng de la opción seleccionada ---
    const lat = option.getAttribute('data-lat');
    const lng = option.getAttribute('data-lng');

    // Asignar a los inputs ocultos (Paso 1)
    actualizarInputs(lat, lng);

    if (selector.value === 'nueva') {
        cardGuardada.classList.add('d-none');
        formNueva.classList.remove('d-none');
        iniciarMapaNueva();
    } else {
        formNueva.classList.add('d-none');
        cardGuardada.classList.remove('d-none');

        document.getElementById('direccion_final_texto').value = option.text;

        document.getElementById('card-dir-alias').innerHTML = `<i class="bi bi-house-door me-2"></i>${option.getAttribute('data-alias')}`;
        document.getElementById('card-dir-texto').innerText = option.getAttribute('data-direccion');

        // Renderizar mapa estático
        const latFloat = parseFloat(lat) || -32.7833;
        const lngFloat = parseFloat(lng) || -71.2000;

        setTimeout(() => {
            if (mapSaved) { mapSaved.remove(); mapSaved = null; }
            mapSaved = L.map('mapa-direccion-guardada', {
                zoomControl: false, dragging: false, scrollWheelZoom: false
            }).setView([latFloat, lngFloat], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapSaved);
            L.marker([latFloat, lngFloat]).addTo(mapSaved);
        }, 300);
    }
}

// Mapa para crear nueva dirección (Draggable)
function iniciarMapaNueva() {
    if (mapNueva) { mapNueva.invalidateSize(); return; }

    // Coordenadas default (Valparaíso/Centro)
    const latDef = -33.0456;
    const lngDef = -71.4025;

    mapNueva = L.map('mapa_seleccion').setView([latDef, lngDef], 10);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(mapNueva);

    markerNueva = L.marker([latDef, lngDef], { draggable: true }).addTo(mapNueva);

    // Eventos de arrastre y click
    markerNueva.on('dragend', function () {
        let pos = markerNueva.getLatLng();
        actualizarInputs(pos.lat, pos.lng);
    });

    mapNueva.on('click', function (e) {
        markerNueva.setLatLng(e.latlng);
        actualizarInputs(e.latlng.lat, e.latlng.lng);
    });

    actualizarInputs(latDef, lngDef);
}

function actualizarInputs(lat, lng) {
    document.getElementById('inputLat').value = lat;
    document.getElementById('inputLng').value = lng;
}

// =========================================================
// 4. SERVICIOS EXTERNOS (BÚSQUEDA Y GUARDADO AJAX)
// =========================================================

async function llamarAPI(query) {
    const url = `https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&limit=1`;
    try {
        const res = await fetch(url);
        const data = await res.json();
        if (data.features && data.features.length > 0) {
            const c = data.features[0].geometry.coordinates;
            return { lat: c[1], lng: c[0] }; // Photon devuelve [lon, lat]
        }
        return null;
    } catch (e) {
        console.error("Error API Mapa:", e);
        return null;
    }
}

async function buscarEnMapa() {
    const dir = document.getElementById('inputDireccion').value;
    const comSelect = document.getElementById('selectComuna');

    if (!dir || comSelect.value === "") {
        Swal.fire('Atención', 'Ingresa calle y selecciona una comuna.', 'warning');
        return;
    }

    const comTxt = comSelect.options[comSelect.selectedIndex].text;

    // Feedback visual en botón
    const btn = document.querySelector('.btn-lupa-azul');
    const htmlOrig = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    btn.disabled = true;

    const res = await llamarAPI(`${dir}, ${comTxt}, Chile`);

    btn.innerHTML = htmlOrig;
    btn.disabled = false;

    if (res) {
        if (!mapNueva) iniciarMapaNueva();
        mapNueva.setView([res.lat, res.lng], 17);
        markerNueva.setLatLng([res.lat, res.lng]);
        actualizarInputs(res.lat, res.lng);
    } else {
        Swal.fire('No encontrado', 'Intenta mover el pin manualmente en el mapa.', 'info');
    }
}

async function centrarMapaEnComuna() {
    const comSelect = document.getElementById('selectComuna');
    if (comSelect.value === "") return;

    const comTxt = comSelect.options[comSelect.selectedIndex].text;
    const res = await llamarAPI(`${comTxt}, Valparaiso, Chile`);

    if (res) {
        if (!mapNueva) iniciarMapaNueva();
        mapNueva.setView([res.lat, res.lng], 13);
        markerNueva.setLatLng([res.lat, res.lng]);
        actualizarInputs(res.lat, res.lng);
    }
}

function guardarDireccionAjax() {
    const btn = document.getElementById('btnGuardarDireccion');

    // Preparar datos
    const fd = new FormData();
    fd.append('etiqueta', document.getElementById('nueva_alias').value || 'Nueva');
    fd.append('direccion', document.getElementById('inputDireccion').value);
    fd.append('comuna_id', document.getElementById('selectComuna').value);
    fd.append('latitud', document.getElementById('inputLat').value);
    fd.append('longitud', document.getElementById('inputLng').value);

    // Validación básica
    if (!fd.get('direccion') || !fd.get('comuna_id')) {
        Swal.fire('Faltan datos', 'Por favor completa la dirección y comuna.', 'error');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

    // Petición AJAX
    fetch(`${CheckoutConfig.baseUrl}perfil/agregarDireccionAjax`, {
        method: 'POST',
        body: fd
    })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: 'Dirección guardada correctamente',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                throw new Error(data.message);
            }
        })
        .catch(e => {
            console.error(e);
            Swal.fire('Error', 'No se pudo guardar la dirección.', 'error');
            btn.disabled = false;
            btn.innerHTML = 'GUARDAR';
        });
}

// =========================================================
// 5. GESTIÓN DE TELÉFONOS
// =========================================================

function mostrarSegundoTelefono() {
    document.getElementById('input-phone-2-container').classList.remove('d-none');
    document.getElementById('btn-add-phone-container').classList.add('d-none');
    document.getElementById('telefono_2').focus();
}

function ocultarSegundoTelefono() {
    document.getElementById('input-phone-2-container').classList.add('d-none');
    document.getElementById('btn-add-phone-container').classList.remove('d-none');
    document.getElementById('telefono_2').value = '';
}

// Manejo del envío final del Checkout
document.getElementById('formCheckout').addEventListener('submit', function(e) {
    // No usamos e.preventDefault() porque queremos que viaje al PHP
    
   // Swal.fire({
     //   title: 'Redirigiendo a Webpay',
       // text: 'Estamos preparando tu pago seguro...',
       // icon: 'info',
        //allowOutsideClick: false,
       // showConfirmButton: false,
        //didOpen: () => {
         //   Swal.showLoading();
       // }
   // });
});