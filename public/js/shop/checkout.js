/**
 * ARCHIVO: checkout.js
 * Descripción: Controlador principal para la vista de Checkout.
 * Requiere: Leaflet, SweetAlert2 y window.CheckoutConfig (inyectado desde PHP).
 */

// ==========================================================================
// 1. VARIABLES GLOBALES Y UTILIDADES
// ==========================================================================
let mapNueva = null, markerNueva = null;
let mapRetiro = null;
let mapSaved = null;
const CheckoutConfig = window.CheckoutConfig || {};

// Utilidad: Formateador de moneda CLP
const formatCLP = (num) => '$' + num.toLocaleString('es-CL');

function capitalizarLetras(input) {
    let palabras = input.value.split(' ');
    for (let i = 0; i < palabras.length; i++) {
        if (palabras[i].length > 0) {
            palabras[i] = palabras[i][0].toUpperCase() + palabras[i].substring(1).toLowerCase();
        }
    }
    input.value = palabras.join(' ');
}


// ==========================================================================
// 2. INICIALIZACIÓN (Al cargar la página)
// ==========================================================================
document.addEventListener("DOMContentLoaded", function () {
    // 1. Inicializar cálculos y vista según el radio button marcado
    cambiarMetodoEntrega();

    // 2. Si despacho está marcado, cargar lógica del mapa
    const radioDespacho = document.getElementById('opcion_despacho');
    if (radioDespacho && radioDespacho.checked) {
        gestionarNuevaDireccion();
    }

    // 3. Bloquear tecla Enter para evitar envíos accidentales
    document.addEventListener("keydown", e => {
        if (e.key === "Enter" && e.target.tagName !== "TEXTAREA") e.preventDefault();
    });

    // 4. Parche de inicialización para Mapas (Admin/Invitado)
    if (CheckoutConfig.esAdmin || CheckoutConfig.esInvitado) {
        const selectorDir = document.getElementById('selector_direccion');
        if (selectorDir) selectorDir.value = "nueva";

        setTimeout(function() {
            try {
                if (mapNueva) mapNueva.invalidateSize();
                if (typeof gestionarNuevaDireccion === 'function') gestionarNuevaDireccion();
                if (typeof centrarMapaEnComuna === 'function') centrarMapaEnComuna();
            } catch (e) {
                console.warn("Aviso: Error menor en inicialización del mapa:", e.message);
            }
        }, 800);
    }

    // 5. Listeners para autocompletar nombres/alias
    const inputAlias = document.querySelector('input[name="nuevo_alias_2"]');
    if (inputAlias) inputAlias.addEventListener('input', (e) => capitalizarLetras(e.target));

    const inputTelN = document.querySelector('input[name="telefono_nuevo_2"]');
    if (inputTelN) {
        inputTelN.setAttribute("placeholder", "Ej: 98765432");
        inputTelN.addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '');
            if (val.length > 8) val = val.substring(0, 8);
            e.target.value = val;
        });
    }
});

// ==========================================================================
// 3. LÓGICA DE UI Y PRECIOS (Métodos de Entrega)
// ==========================================================================
function cambiarMetodoEntrega() {
    const esRetiro = document.getElementById('opcion_retiro').checked;
    const bloqueRetiro = document.getElementById('bloque_retiro');
    const bloqueDespacho = document.getElementById('bloque_despacho');
    const alertaEnvio = document.getElementById('alerta-envio-gratis');
    const labelEnvio = document.getElementById('resumen-costo-envio');
    const labelTotal = document.getElementById('resumen-total-final');
    const inputSucursal = document.getElementById('input_sucursal_codigo');

    bloqueRetiro.classList.toggle('d-none', !esRetiro);
    bloqueDespacho.classList.toggle('d-none', esRetiro);

    let costoFinalEnvio = 0;

    if (esRetiro) {
        costoFinalEnvio = 0;
        if (alertaEnvio) alertaEnvio.classList.add('d-none');
        if (inputSucursal) inputSucursal.disabled = false;
        
        const comboComuna = document.getElementById('filtro_comuna_retiro');
        if (comboComuna && comboComuna.value) gestionarRetiroAutomatico();
    } else {
        const { totalCarro, umbralGratis, umbralAlerta, costoDespacho } = CheckoutConfig;
        if (totalCarro >= umbralGratis) {
            costoFinalEnvio = 0;
            if (alertaEnvio) alertaEnvio.classList.add('d-none');
        } else {
            costoFinalEnvio = costoDespacho;
            if (totalCarro >= umbralAlerta && alertaEnvio) alertaEnvio.classList.remove('d-none');
        }
        if (inputSucursal) inputSucursal.disabled = true;
        gestionarNuevaDireccion(); 
    }

    if(labelEnvio) {
        labelEnvio.innerHTML = (costoFinalEnvio === 0) 
            ? '<span class="text-success fw-bold">GRATIS</span>' 
            : formatCLP(costoFinalEnvio);
    }

    const costoServicioFijo = CheckoutConfig.costoServicio || 490;
    const totalRealPantalla = CheckoutConfig.totalCarro + costoFinalEnvio + costoServicioFijo;
    if(labelTotal) labelTotal.innerText = formatCLP(totalRealPantalla);
}

function gestionarRetiroAutomatico() {
    const comunaSel = document.getElementById('filtro_comuna_retiro').value;
    const container = document.getElementById('contenedor-detalle-sucursal');
    const inputHidden = document.getElementById('input_sucursal_codigo');

    if (!comunaSel) {
        container.classList.add('d-none');
        inputHidden.value = "";
        return;
    }

    const sucursal = CheckoutConfig.sucursales.find(s =>
        s.comuna.toLowerCase().includes(comunaSel.toLowerCase()) ||
        s.direccion.toLowerCase().includes(comunaSel.toLowerCase())
    );

    if (sucursal) {
        document.getElementById('card-sucursal-nombre').innerText = sucursal.nombre;
        document.getElementById('card-sucursal-dir').innerText = sucursal.direccion;
        
        const fonoEl = document.getElementById('card-sucursal-fono');
        if(fonoEl) fonoEl.innerText = sucursal.fono || 'No disponible';
        
        const horEl = document.getElementById('card-sucursal-horario');
        if(horEl) horEl.innerText = sucursal.horario;

        inputHidden.value = sucursal.codigo;
        container.classList.remove('d-none');

        setTimeout(() => {
            if (mapRetiro) { mapRetiro.remove(); mapRetiro = null; }
            let lat = sucursal.lat || -32.7833;
            let lng = sucursal.lng || -71.2000;
            mapRetiro = L.map('mapa-retiro-inline').setView([lat, lng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapRetiro);
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

// ==========================================================================
// 4. LÓGICA DE MAPAS Y DIRECCIONES (Despacho)
// ==========================================================================
function gestionarNuevaDireccion() {
    const selector = document.getElementById('selector_direccion');
    const formNueva = document.getElementById('form_nueva_direccion');
    const cardGuardada = document.getElementById('contenedor-detalle-direccion');

    if (!selector) return;

    const option = selector.options[selector.selectedIndex];
    const lat = option.getAttribute('data-lat');
    const lng = option.getAttribute('data-lng');

    actualizarInputs(lat, lng);

    if (selector.value === 'nueva') {
        if(cardGuardada) cardGuardada.classList.add('d-none');
        if(formNueva) formNueva.classList.remove('d-none');
        iniciarMapaNueva();
    } else {
        if(formNueva) formNueva.classList.add('d-none');
        if(cardGuardada) cardGuardada.classList.remove('d-none');

        document.getElementById('direccion_final_texto').value = option.text;
        document.getElementById('card-dir-alias').innerHTML = `<i class="bi bi-house-door me-2"></i>${option.getAttribute('data-alias')}`;
        document.getElementById('card-dir-texto').innerText = option.getAttribute('data-direccion');

        const latFloat = parseFloat(lat) || -32.7833;
        const lngFloat = parseFloat(lng) || -71.2000;

        setTimeout(() => {
            if (mapSaved) { mapSaved.remove(); mapSaved = null; }
            mapSaved = L.map('mapa-direccion-guardada', { zoomControl: false, dragging: false, scrollWheelZoom: false }).setView([latFloat, lngFloat], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapSaved);
            L.marker([latFloat, lngFloat]).addTo(mapSaved);
        }, 300);
    }
}

function iniciarMapaNueva() {
    if (mapNueva) { mapNueva.invalidateSize(); return; }
    const latDef = -33.0456, lngDef = -71.4025; // Valparaíso Centro

    mapNueva = L.map('mapa_seleccion').setView([latDef, lngDef], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapNueva);
    markerNueva = L.marker([latDef, lngDef], { draggable: true }).addTo(mapNueva);

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
    const inputLat = document.getElementById('inputLat');
    const inputLng = document.getElementById('inputLng');
    if (inputLat) inputLat.value = lat;
    if (inputLng) inputLng.value = lng;
}

// ==========================================================================
// 5. SERVICIOS EXTERNOS (APIs Y AJAX)
// ==========================================================================
async function llamarAPI(query) {
    try {
        const res = await fetch(`https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&limit=1`);
        const data = await res.json();
        if (data.features && data.features.length > 0) {
            const c = data.features[0].geometry.coordinates;
            return { lat: c[1], lng: c[0] }; 
        }
        return null;
    } catch (e) { console.error("Error API Mapa:", e); return null; }
}

async function buscarEnMapa() {
    const dir = document.getElementById('inputDireccion').value;
    const comSelect = document.getElementById('selectComuna');
    if (!dir || comSelect.value === "") { Swal.fire('Atención', 'Ingresa calle y selecciona una comuna.', 'warning'); return; }

    const comTxt = comSelect.options[comSelect.selectedIndex].text;
    const btn = document.querySelector('.btn-lupa-azul') || document.querySelector('.btn-white');
    const htmlOrig = btn.innerHTML;
    
    btn.innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span>';
    btn.disabled = true;

    const res = await llamarAPI(`${dir}, ${comTxt}, Chile`);
    btn.innerHTML = htmlOrig; btn.disabled = false;

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

// ==========================================================================
// 6. GESTIÓN DE CLIENTES Y TELÉFONOS (Venta Asistida / Perfil)
// ==========================================================================
function verificarRutCliente(rut) {
    if (rut.trim() === '') return;
    const elNombre = document.getElementById('asistido_p_nombre');
    const elApellido = document.getElementById('asistido_p_apellido');
    if(elNombre) elNombre.placeholder = 'Buscando cliente...';

    fetch(CheckoutConfig.baseUrl + 'admin/buscar_cliente_venta_asistida', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ rut: rut })
    }).then(res => res.json()).then(data => {
        const opcionCredito = document.getElementById('opcion_credito_confianza');
        if (data.status === 'success') {
            let partes = data.nombre.trim().split(' ');
            if(elNombre) elNombre.value = partes[0] || '';
            if(elApellido) elApellido.value = (partes.length > 1) ? partes.slice(1).join(' ') : '';

            if (data.es_cliente_confianza == 1 && opcionCredito) {
                opcionCredito.classList.remove('d-none');
                opcionCredito.classList.add('d-flex', 'align-items-center');
                const radioCredito = document.getElementById('pago_credito');
                if(radioCredito) radioCredito.checked = true;
                gestionarFolio();
                Swal.fire({ icon: 'success', title: 'Cliente Detectado', text: 'Se habilitó el pago con Crédito.', toast: true, position: 'top-end', timer: 3500, showConfirmButton: false });
            } else { ocultarCredito(opcionCredito); }
        } else {
            if(elNombre) elNombre.placeholder = '';
            ocultarCredito(opcionCredito);
        }
    }).catch(err => { console.error('Error:', err); if(elNombre) elNombre.placeholder = ''; });
}

function ocultarCredito(elemento) {
    if(!elemento) return;
    elemento.classList.add('d-none'); elemento.classList.remove('d-flex', 'align-items-center');
    const radioCredito = document.getElementById('pago_credito');
    const radioTienda = document.getElementById('pago_tienda');
    if (radioCredito && radioCredito.checked && radioTienda) radioTienda.checked = true;
}

function gestionarFolio() {
    const radioCredito = document.getElementById('pago_credito');
    const isCredito = radioCredito ? radioCredito.checked : false;
    const cajaFolio = document.getElementById('caja_folio');
    const inputFolio = document.getElementById('folio_documento');

    if (cajaFolio && inputFolio) {
        if (isCredito) { cajaFolio.classList.add('opacity-50'); inputFolio.value = ''; inputFolio.disabled = true; } 
        else { cajaFolio.classList.remove('opacity-50'); inputFolio.disabled = false; }
    }
}

function gestionarSegundoTelefono() {
    const sel = document.getElementById('selector_segundo_contacto');
    const wrapper = document.getElementById('wrapper_nuevo_segundo_tel');
    if (wrapper) wrapper.classList.toggle('d-none', sel.value !== 'nuevo');

    const saveHidden = document.getElementById('hidden_save_tel2');
    if (saveHidden) saveHidden.value = "0";
    const btn = document.getElementById('btnConfirmarTel');
    if (btn) { btn.className = "btn btn-sm btn-outline-cenco-indigo rounded-pill fw-bold px-3 transition-hover"; btn.innerHTML = "Vincular Contacto"; }
}

function confirmarNuevoTelefono() {
    const num = document.getElementById('telefono_nuevo_2').value;
    if (!num || num.length < 8) { Swal.fire('Falta el número', 'Por favor, ingresa un número válido.', 'warning'); return; }
    document.getElementById('hidden_save_tel2').value = "1";
    const btn = document.getElementById('btnConfirmarTel');
    btn.className = "btn btn-sm btn-cenco-green rounded-pill fw-bold px-3 text-white shadow-sm";
    btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> ¡Vinculado!';
}

// ==========================================================================
// 7. MODAL RESUMEN Y PROCESO DE PAGO
// ==========================================================================
function abrirModalResumen() {
    const tipoEntregaEl = document.querySelector('input[name="tipo_entrega"]:checked');
    if (!tipoEntregaEl) return;
    const tipoEntrega = tipoEntregaEl.value;

    const esAsistida = CheckoutConfig.esAdmin;
    const esInvitado = CheckoutConfig.esInvitado;

    // Validar Datos antes de abrir el modal
    if (tipoEntrega == 2) {
        const sucVal = document.getElementById('input_sucursal_codigo') ? document.getElementById('input_sucursal_codigo').value : '';
        if (!sucVal) { Swal.fire('Falta información', 'Selecciona la sucursal de retiro.', 'warning'); return; }
    } else {
        if (esAsistida || esInvitado) {
            const calle = document.getElementById('inputDireccion').value;
            const selectComuna = document.getElementById('selectComuna');
            if (!calle || !selectComuna || selectComuna.value === '') { Swal.fire('Faltan Datos', 'Busca en el mapa la calle y comuna.', 'warning'); return; }
            document.getElementById('direccion_final_texto').value = calle + ', ' + selectComuna.options[selectComuna.selectedIndex].text;
        } else {
            const dirSel = document.getElementById('selector_direccion');
            if (dirSel && dirSel.value === 'nueva' && document.getElementById('inputDireccion').value.trim() === '') { Swal.fire('Falta información', 'Ingresa tu calle.', 'warning'); return; }
            if (dirSel && dirSel.value === 'nueva' && document.getElementById('selectComuna').value === '') { Swal.fire('Falta información', 'Selecciona comuna.', 'warning'); return; }
        }
    }

    let nombreClienteSeguro = CheckoutConfig.nombreCliente;

    if (esAsistida) {
        const pNombre = document.getElementById('asistido_p_nombre') ? document.getElementById('asistido_p_nombre').value.trim() : '';
        const pApellido = document.getElementById('asistido_p_apellido') ? document.getElementById('asistido_p_apellido').value.trim() : '';
        const rutAsistido = document.getElementById('asistido_rut') ? document.getElementById('asistido_rut').value.trim() : '';
        if (!rutAsistido || !pNombre || !pApellido) { Swal.fire('Faltan Datos', 'Completa RUT y Nombre del cliente.', 'warning'); return; }
        nombreClienteSeguro = (pNombre + ' ' + pApellido).trim();
    }

    const resumenNombreEl = document.getElementById('resumen-nombre');
    if (resumenNombreEl) resumenNombreEl.innerText = nombreClienteSeguro;

    let telPrincipalVisual = '';
    if (esAsistida) telPrincipalVisual = document.getElementById('asistido_telefono') ? document.getElementById('asistido_telefono').value.trim() : '';
    else if (esInvitado) { const telInput = document.querySelector('input[name="telefono_contacto"]'); if (telInput) telPrincipalVisual = telInput.value.trim(); } 
    else telPrincipalVisual = CheckoutConfig.telefonoPrincipal;

    let htmlTelefonos = (telPrincipalVisual !== '') ? `<div class="mb-1"><i class="bi bi-telephone text-success me-1"></i> <strong>Contacto:</strong> +569${telPrincipalVisual.replace('+569','')}</div>` : `<div class="mb-1 text-muted small"><i class="bi bi-telephone-x me-1"></i> Sin teléfono registrado</div>`;

    if (!esAsistida && !esInvitado) {
        const segSel = document.getElementById('selector_segundo_contacto');
        if (segSel && segSel.value) {
            if (segSel.value === 'nuevo') {
                let guardado = document.getElementById('hidden_save_tel2') ? document.getElementById('hidden_save_tel2').value : '0';
                let telNuevo = document.getElementById('telefono_nuevo_2') ? document.getElementById('telefono_nuevo_2').value : '';
                if (telNuevo && guardado === "1") htmlTelefonos += `<div><i class="bi bi-telephone-plus text-primary me-1"></i> <strong>Alternativo:</strong> ${telNuevo}</div>`;
                else if (telNuevo && guardado === "0") { Swal.fire('Falta un paso', 'Presiona "Vincular Contacto".', 'info'); return; }
            } else {
                htmlTelefonos += `<div><i class="bi bi-telephone-plus text-primary me-1"></i> <strong>Alternativo:</strong> ${segSel.value}</div>`;
            }
        }
    }

    if (esInvitado) {
        const telInput = document.querySelector('input[name="telefono_contacto"]');
        if (!telInput || !telInput.value) { Swal.fire('Teléfono Obligatorio', 'Ingresa tu número telefónico.', 'warning'); return; }
    }

    const resumenTelefonoEl = document.getElementById('resumen-telefono');
    if(resumenTelefonoEl) resumenTelefonoEl.innerHTML = htmlTelefonos;

    const tituloEntrega = document.getElementById('resumen-titulo-entrega');
    const detalleEntrega = document.getElementById('resumen-detalle-entrega');

    if (tipoEntrega == '1') {
        if(tituloEntrega) tituloEntrega.innerHTML = '<i class="bi bi-house-door me-2"></i>Despacho a Domicilio';
        let direccionEscogida = '';
        if (esAsistida || esInvitado) {
            const dirInput = document.getElementById('direccion_final_texto');
            if(dirInput) direccionEscogida = dirInput.value;
        } else {
            let dirSelect = document.getElementById('selector_direccion');
            if (dirSelect) direccionEscogida = (dirSelect.value === 'nueva') ? document.getElementById('inputDireccion').value : dirSelect.options[dirSelect.selectedIndex].getAttribute('data-direccion');
        }

        let now = new Date(); let procDate = new Date(now);
        if (esAsistida) {
            let entregaStr = '';
            if (now.getHours() < 14) { procDate.setHours(procDate.getHours() + 3); entregaStr = `Hoy a las ${procDate.getHours().toString().padStart(2, '0')}:${procDate.getMinutes().toString().padStart(2, '0')} hrs.`; } 
            else {
                procDate.setDate(procDate.getDate() + 1); while (procDate.getDay() === 6 || procDate.getDay() === 0) { procDate.setDate(procDate.getDate() + 1); }
                const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'], meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                entregaStr = `El ${dias[procDate.getDay()]} ${procDate.getDate()} de ${meses[procDate.getMonth()]}, mañana.`;
            }
            if(detalleEntrega) detalleEntrega.innerHTML = `<div class="mb-2"><strong><i class="bi bi-geo-alt-fill text-danger me-1"></i> Dirección:</strong><br><span class="ms-4 text-muted">${direccionEscogida}</span></div><div><strong><i class="bi bi-truck text-success me-1"></i> Envío Express:</strong><br><span class="ms-4 badge bg-success text-white px-2 py-1 mt-1">${entregaStr}</span></div>`;
        } else {
            if (procDate.getDay() === 6 || procDate.getDay() === 0 || procDate.getHours() >= 15) { procDate.setDate(procDate.getDate() + 1); while (procDate.getDay() === 6 || procDate.getDay() === 0) { procDate.setDate(procDate.getDate() + 1); } }
            let deliveryDate = new Date(procDate); let daysToAdd = 2;
            while (daysToAdd > 0) { deliveryDate.setDate(deliveryDate.getDate() + 1); if (deliveryDate.getDay() !== 6 && deliveryDate.getDay() !== 0) { daysToAdd--; } }
            const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'], meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            if(detalleEntrega) detalleEntrega.innerHTML = `<div class="mb-2"><strong><i class="bi bi-geo-alt-fill text-danger me-1"></i> Dirección:</strong><br><span class="ms-4 text-muted">${direccionEscogida}</span></div><div><strong><i class="bi bi-truck text-success me-1"></i> Llega:</strong><br><span class="ms-4 badge bg-success text-white px-2 py-1 mt-1">El ${dias[deliveryDate.getDay()]} ${deliveryDate.getDate()} de ${meses[deliveryDate.getMonth()]}</span></div>`;
        }
    } else {
        if(tituloEntrega) tituloEntrega.innerHTML = '<i class="bi bi-shop me-2"></i>Retiro en Tienda';
        const cardNombre = document.getElementById('card-sucursal-nombre'), cardDir = document.getElementById('card-sucursal-dir');
        let sucNombre = cardNombre ? cardNombre.innerText : 'Sucursal Asignada', sucDir = cardDir ? cardDir.innerText : '';
        let htmlInfo = `<div class="mb-1"><strong>${sucNombre}</strong></div><div class="small text-muted mb-1"><i class="bi bi-geo-alt me-1"></i> ${sucDir}</div>`;

        if (esAsistida) htmlInfo += `<div class="alert alert-info py-2 mt-3 mb-0 border-0" style="font-size: 0.75rem;"><i class="bi bi-check-circle-fill me-1"></i> Entrega inmediata en mostrador.</div>`;
        else {
            const ahora = new Date(); let horaBase = ahora.getHours(); if (ahora.getMinutes() >= 30) horaBase++; let horaCalculada = horaBase + 3; if (horaCalculada >= 24) horaCalculada -= 24;
            htmlInfo += `<div class="alert alert-info py-2 mt-3 mb-0 border-0" style="font-size: 0.75rem;"><i class="bi bi-clock-history me-1"></i> Listo a partir de las <strong>${horaCalculada.toString().padStart(2, '0')}:00 hrs</strong>.</div>`;
        }
        if(detalleEntrega) detalleEntrega.innerHTML = htmlInfo;
    }

    const listaUl = document.getElementById('resumen-lista-productos');
    if (listaUl) {
        listaUl.innerHTML = '';
        document.querySelectorAll('.item-carrito-resumen').forEach(el => {
            const nombreEl = el.querySelector('.nombre-item-resumen'), qtyEl = el.querySelector('.qty-badge');
            if (nombreEl && qtyEl) listaUl.innerHTML += `<li class="list-group-item px-2 py-2 d-flex justify-content-between align-items-center bg-transparent border-light"><span class="text-truncate me-3" style="max-width: 80%;">${nombreEl.innerText}</span><span class="badge bg-secondary rounded-pill">x${qtyEl.innerText}</span></li>`;
        });
    }

    const alertaPagoTexto = document.getElementById('texto-advertencia-pago');
    if (alertaPagoTexto) {
        if (esAsistida) {
            let accionLogistica = (tipoEntrega == '1') ? 'despachar al domicilio' : 'entregar en la sucursal';
            const metodoPagoSelAdmin = document.querySelector('input[name="metodo_pago"]:checked');
            if (metodoPagoSelAdmin && metodoPagoSelAdmin.value === 'credito_confianza') alertaPagoTexto.innerHTML = `<strong>Crédito:</strong> El cliente paga con su Línea. Puedes <strong>${accionLogistica}</strong> los productos sin comprobante inmediato.`;
            else alertaPagoTexto.innerHTML = `<strong>Venta Asistida:</strong> Verifica el pago y sube el comprobante antes de <strong>${accionLogistica}</strong> los productos.`;
        } else {
            const metodoPagoSel = document.querySelector('input[name="metodo_pago"]:checked');
            if (metodoPagoSel && metodoPagoSel.value === 'contra_entrega') alertaPagoTexto.innerHTML = `Debes realizar el pago al recibir. <br><span class="fw-normal text-muted">Si no hay stock, te contactaremos.</span>`;
            else alertaPagoTexto.innerHTML = `Si no hay stock de algún producto, te contactaremos para coordinar.`;
        }
    }

    let costoEnvioMonto = (tipoEntrega == '1' && CheckoutConfig.totalCarro < CheckoutConfig.umbralGratis) ? CheckoutConfig.costoDespacho : 0;
    if (listaUl) listaUl.innerHTML += `<li class="list-group-item px-2 py-2 d-flex justify-content-between bg-light border-top mt-1"><span class="text-muted small"><i class="bi bi-bag-check me-1"></i> Costo Servicio</span><span class="fw-bold small">+$${CheckoutConfig.costoServicio}</span></li><li class="list-group-item px-2 py-2 d-flex justify-content-between bg-light mb-2"><span class="text-muted small"><i class="bi bi-truck me-1"></i> Despacho</span><span class="fw-bold small">${costoEnvioMonto === 0 ? '<span class="text-success fw-bold">GRATIS</span>' : '+$' + costoEnvioMonto}</span></li>`;

    const totalFinal = document.getElementById('resumen-total-final'), resumenTotalMonto = document.getElementById('resumen-total-monto');
    if (totalFinal && resumenTotalMonto) resumenTotalMonto.innerText = totalFinal.innerText;

    const modalResumen = document.getElementById('modalResumenCompra');
    if (modalResumen) new bootstrap.Modal(modalResumen).show();
}

function procesarPagoFinal() {
    const form = document.getElementById('formCheckout');
    const metodoPagoSeleccionado = document.querySelector('input[name="metodo_pago"]:checked');
    let metodoPagoValor = metodoPagoSeleccionado ? metodoPagoSeleccionado.value : 'webpay';

    if (metodoPagoSeleccionado) {
        let hiddenPago = document.getElementById('hidden_metodo_pago');
        if (hiddenPago) hiddenPago.remove();
        hiddenPago = document.createElement('input');
        hiddenPago.type = 'hidden'; hiddenPago.name = 'metodo_pago_final'; hiddenPago.id = 'hidden_metodo_pago'; hiddenPago.value = metodoPagoValor;
        form.appendChild(hiddenPago);
    }

    let alertaTitulo = 'Procesando...', alertaTexto = 'Por favor, espera un momento.';
    if (metodoPagoValor === 'webpay') { alertaTitulo = 'Conectando con Transbank'; alertaTexto = 'Preparando tu pago seguro...'; }
    else if (metodoPagoValor === 'pago_tienda') { alertaTitulo = 'Registrando Venta Asistida'; alertaTexto = 'Guardando el pedido local...'; }
    else if (metodoPagoValor === 'contra_entrega') { alertaTitulo = 'Confirmando Pedido'; alertaTexto = 'Registrando pago contra entrega...'; }

    Swal.fire({ title: alertaTitulo, text: alertaTexto, icon: 'info', allowOutsideClick: false, showConfirmButton: false, didOpen: () => { Swal.showLoading(); } });
    form.submit();
}

function confirmarDireccionCheckout() {
    if (CheckoutConfig.esInvitado || CheckoutConfig.esAdmin) {
        const calleEl = document.getElementById('inputDireccion');
        const selectComuna = document.getElementById('selectComuna');
        if (!calleEl || !selectComuna || !calleEl.value || selectComuna.value === '') { Swal.fire('Faltan Datos', 'Ingresa la calle y selecciona una comuna.', 'warning'); return; }
        const dirFinal = document.getElementById('direccion_final_texto');
        if (dirFinal) dirFinal.value = calleEl.value + ', ' + selectComuna.options[selectComuna.selectedIndex].text;
        Swal.fire({ icon: 'success', title: '¡Confirmada!', text: 'Dirección de despacho agregada.', confirmButtonColor: '#76C043' });
    } else {
        if (typeof guardarDireccionAjax === 'function') guardarDireccionAjax();
    }
}